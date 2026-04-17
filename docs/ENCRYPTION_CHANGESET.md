# Field-Level Encryption & Audit Log — Project Record

**Branch:** `feature-db-encryption`
**Git restore tag:** `pre-ebcryption-stable` *(note: typo in tag name — recorded exactly as executed)*
**Date:** 2026-03-14
**Status:** PENDING CONFIRMATION — no implementation changes applied yet

---

## 1. Introduction & Context

### Why this work is being done

The dental clinic application holds sensitive personal and financial data across three
core tables: `patients`, `patients_clinical`, and `insurance`. This data includes
dates of birth, contact details, clinical diagnoses, treatment descriptions, financial
amounts, and insurance policy numbers. While the application enforces authentication
and role-based access at the UI level, the underlying MySQL database rows are stored
in plaintext. Anyone with direct database access — via phpMyAdmin, a database dump,
or a misconfigured backup — can read patient records without any application-level
credentials.

This implementation adds two independent layers of protection:

1. **Field-level AES-256-CBC encryption** on all sensitive columns, using Laravel's
   built-in `Crypt` facade and Eloquent cast system. Data becomes unreadable at the
   database level while remaining fully transparent to the application.

2. **A tamper-evident audit log** that records every create, update, and delete event
   with: who performed it (user ID + name snapshot), what changed (before/after field
   values), when (precise timestamp), and from where (IP address + User-Agent).

### Database scope

Two separate MySQL databases are in use:

- `792088_mint` — Mint Dental Clinic (primary, `DB_CONNECTION`)
- `792088_tanya` — Tanya Clinic (partner, `DB_PARTNER_*`)

Both share the same `APP_KEY` (`base64:oNpNkmFA70sAIo+77PHQMUAQ8RClaRo0VbU36B1kXQQ=`)
and therefore the same encryption key. Migrations and the `encrypt:existing` /
`decrypt:existing` Artisan commands must be run against both databases.
The `audit_logs` table will be created in the primary database only.

### Encryption cipher

Laravel uses **AES-256-CBC** via `config/app.php → 'cipher' => 'AES-256-CBC'`.
The `APP_KEY` is the sole encryption key. Losing this key means losing all encrypted
data permanently. Ensure the key is backed up outside the codebase.

---

## 2. Safety Nets & Restore Strategy

Three independent rollback layers are in place before any code is written.

### Layer 1 — Git tag (instant code rollback)

```bash
git tag pre-ebcryption-stable   # already executed
```

Restores all source files to their pre-implementation state instantly:

```bash
git checkout pre-ebcryption-stable
```

The working branch `feature-db-encryption` is unaffected; the tag is a fixed pointer.

### Layer 2 — This document

`docs/ENCRYPTION_CHANGESET.md` records every file to be created or modified, the
exact before/after for every change, every migration with its full `down()` method,
the strict execution order, and the complete rollback procedure. It is committed to
the repository and serves as the authoritative audit trail for this work.

### Layer 3 — `decrypt:existing` Artisan command (database rollback)

Built alongside `encrypt:existing`, this command reverses the encryption of all rows
in the database — restoring plaintext values — using `Crypt::decryptString()` with
graceful handling of values that were never encrypted. Run this before `git checkout`
if the models are already live and reading encrypted data.

### Database backups

Both `792088_mint` and `792088_tanya` databases have been manually backed up by the
developer before this branch was created. These are the ultimate fallback.

### Complete rollback sequence

```bash
# Step R1 — restore plaintext in both databases
php artisan decrypt:existing

# Step R2 — restore all source code files
git checkout pre-ebcryption-stable

# Step R3 — drop audit_logs table and revert insurance_no column type
php artisan migrate:rollback --step=2
```

---

## 3. Pre-Implementation Verification: `date_of_birth` & Carbon Age

Before finalising the field list, a specific concern was raised and verified:
**would encrypting `date_of_birth` break age calculation and display in Blade?**

### Findings

The encrypted cast transparently decrypts `date_of_birth` before Blade sees it,
returning the stored `Y-m-d` string (e.g. `"1985-04-23"`). Every usage across the
views was checked:

| Blade usage | File(s) | Safe after encryption? |
|---|---|---|
| `$patient->age` | `show.blade.php`, `show_partner.blade.php` | ✅ `getAgeAttribute()` calls `Carbon::parse($this->date_of_birth)->age` — cast decrypts first |
| `Carbon::parse($patient->date_of_birth)->age` | `edit.blade.php`, `create.blade.php`, `patient_details.blade.php` | ✅ cast decrypts before Carbon receives the value |
| `Carbon::parse($patient->date_of_birth)->format('Y-m-d')` | `patient_details.blade.php` (edit form) | ✅ same |
| `$remotePatient->date_of_birth` | `partner_search.blade.php` | ✅ same APP_KEY on partner DB |

**Conclusion: age display is unaffected by encryption. ✅**

### Conflict identified and resolved

The existing `setDateOfBirthAttribute()` old-style mutator writes directly to
`$this->attributes['date_of_birth']`, bypassing the `$casts` pipeline entirely.
If left in place, the d/m/Y → Y-m-d conversion would still run but the encrypted
cast would never fire — values would be stored in plaintext.

**Fix:** replace both the mutator and the current `'date_of_birth' => 'string'` cast
with a single custom cast class `app/Casts/EncryptedDate.php` whose `set()` method
handles the format conversion AND encryption, and whose `get()` method handles
decryption. The mutator is removed entirely.

---

## 4. Implementation Plan

### 4.1 Fields to encrypt

**`patients` table**

| Field | Encrypt | Reason if NO |
|---|---|---|
| `patient_id` | NO | Primary key |
| `name` | NO | Used in `LIKE` search — requires blind index (future) |
| `acc_no` | NO | Searched + used as FK reference |
| `active` | NO | Clinic flag used in conditionals |
| `opened`, `closed` | NO | Not sensitive |
| `date_of_birth` | YES | Via custom `EncryptedDate` cast |
| `tel` | YES | |
| `email` | YES | |
| `gender` | YES | |
| `location` | YES | |
| `pobox` | YES | |
| `town` | YES | |
| `occupation` | YES | |
| `remarks` | YES | |

**`patients_clinical` table**

| Field | Encrypt | Reason if NO |
|---|---|---|
| `patient_clinic_id` | NO | Primary key |
| `patient_id`, `patient_id_ver`, `acc_no` | NO | FK / join keys |
| `date` | NO | Used in `ORDER BY date DESC` |
| `time_stamp` | NO | Used in `WHERE time_stamp >=` (dashboard, index) |
| `is_insurance_claim` | NO | Boolean flag used in conditionals |
| `insurance_provider_id` | NO | FK to insurance_providers |
| `diagnostic` | YES | |
| `description` | YES | |
| `tooth` | YES | |
| `amount` | YES | |
| `paid` | YES | |
| `balance` | YES | |
| `estimate_description` | YES | |
| `estimate` | YES | |
| `estimate_cost` | YES | |
| `estimate_paid` | YES | |
| `estimate_balance` | YES | |
| `notes` | YES | |
| `remarks` | YES | |

**`insurance` table**

| Field | Encrypt | Reason if NO |
|---|---|---|
| `InsuranceID` | NO | Primary key |
| `ver_patient_id`, `ver_acc_no`, `provider_id` | NO | FK references |
| `policy_status` | NO | Used in `WHERE policy_status = 1` |
| `insurance_no` | YES | Requires INT → TEXT migration first |
| `insurance_id_no` | YES | |
| `insurance_provider` | YES | |
| `insurance_remarks` | YES | |
| `invalidation_reason` | YES | |

---

### 4.2 Execution order (strict — do not reorder)

```
Step 0    Create app/Casts/EncryptedDate.php              [new file]
Step 1a   php artisan migrate  (insurance_no → TEXT)      [schema change]
Step 1b   php artisan migrate  (create audit_logs)        [schema change]
Step 2    php artisan encrypt:existing                    [encrypts live data]
Step 3    Update Patient.php $casts + remove mutator      [model change]
Step 4    Update PatientClinical.php $casts               [model change]
Step 5    Update Insurance.php $casts                     [model change]
Step 6    Create three Observer files                     [new files]
Step 7    Update AppServiceProvider (register observers)  [minor modification]
Step 8    php artisan tinker → verification               [read-only check]
```

> **Critical:** Step 2 must complete successfully before Steps 3–5.
> If model casts are live before existing rows are encrypted, every read of a
> sensitive field will throw `DecryptException`.

---

### 4.3 Files to create

| File | Purpose |
|---|---|
| `app/Casts/EncryptedDate.php` | Custom cast: d/m/Y format handling + AES-256 encryption |
| `app/Models/AuditLog.php` | Eloquent model for the audit_logs table |
| `app/Observers/PatientObserver.php` | Audit logging for Patient events |
| `app/Observers/PatientClinicalObserver.php` | Audit logging for PatientClinical events |
| `app/Observers/InsuranceObserver.php` | Audit logging for Insurance events |
| `app/Console/Commands/EncryptExistingData.php` | Encrypts all existing rows via raw DB |
| `app/Console/Commands/DecryptExistingData.php` | Reverses encryption (rollback tool) |
| `database/migrations/…_convert_insurance_no_to_text.php` | Schema: INT → TEXT |
| `database/migrations/…_create_audit_logs_table.php` | Schema: new audit_logs table |

---

### 4.4 Files to modify

| File | Change |
|---|---|
| `app/Models/Patient.php` | Add 9 encrypted casts; replace old mutator with `EncryptedDate` cast |
| `app/Models/PatientClinical.php` | Add 13 encrypted casts |
| `app/Models/Insurance.php` | Add 5 encrypted casts |
| `app/Providers/AppServiceProvider.php` | Register 3 observers in `boot()` |

---

### 4.5 Model changes in detail

#### `app/Models/Patient.php`

Remove:
```php
public function setDateOfBirthAttribute($value) { ... }  // entire method removed
```

Replace `$casts`:
```php
// BEFORE:
protected $casts = [
    'date_of_birth' => 'string',
    'active'        => 'integer',
    'opened'        => 'date:Y-m-d',
];

// AFTER:
protected $casts = [
    'date_of_birth' => \App\Casts\EncryptedDate::class,
    'tel'           => 'encrypted',
    'email'         => 'encrypted',
    'gender'        => 'encrypted',
    'location'      => 'encrypted',
    'pobox'         => 'encrypted',
    'town'          => 'encrypted',
    'occupation'    => 'encrypted',
    'remarks'       => 'encrypted',
    'active'        => 'integer',
    'opened'        => 'date:Y-m-d',
];
```

#### `app/Models/PatientClinical.php`

Add `$casts` (currently none):
```php
protected $casts = [
    'diagnostic'           => 'encrypted',
    'description'          => 'encrypted',
    'tooth'                => 'encrypted',
    'amount'               => 'encrypted',
    'paid'                 => 'encrypted',
    'balance'              => 'encrypted',
    'estimate_description' => 'encrypted',
    'estimate'             => 'encrypted',
    'estimate_cost'        => 'encrypted',
    'estimate_paid'        => 'encrypted',
    'estimate_balance'     => 'encrypted',
    'notes'                => 'encrypted',
    'remarks'              => 'encrypted',
];
```

#### `app/Models/Insurance.php`

Add `$casts` (currently none):
```php
protected $casts = [
    'insurance_no'        => 'encrypted',
    'insurance_id_no'     => 'encrypted',
    'insurance_provider'  => 'encrypted',
    'insurance_remarks'   => 'encrypted',
    'invalidation_reason' => 'encrypted',
];
```

#### `app/Providers/AppServiceProvider.php`

Add inside `boot()`:
```php
\App\Models\Patient::observe(\App\Observers\PatientObserver::class);
\App\Models\PatientClinical::observe(\App\Observers\PatientClinicalObserver::class);
\App\Models\Insurance::observe(\App\Observers\InsuranceObserver::class);
```

---

### 4.6 Migration detail

#### Migration A — convert_insurance_no_to_text

```php
public function up(): void
{
    Schema::table('insurance', function (Blueprint $table) {
        $table->text('insurance_no')->nullable()->change();
    });
}

public function down(): void
{
    // WARNING: run decrypt:existing before rolling back this migration
    Schema::table('insurance', function (Blueprint $table) {
        $table->integer('insurance_no')->nullable()->change();
    });
}
```

#### Migration B — create_audit_logs_table

```php
public function up(): void
{
    Schema::create('audit_logs', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id')->nullable();
        $table->string('user_name')->nullable();
        $table->string('action');                 // created / updated / deleted
        $table->string('model_type');             // Patient / PatientClinical / Insurance
        $table->string('model_id');
        $table->json('before')->nullable();       // decrypted values before change
        $table->json('after')->nullable();        // decrypted values after change
        $table->ipAddress('ip_address')->nullable();
        $table->text('user_agent')->nullable();
        $table->timestamp('created_at')->useCurrent();
    });
}

public function down(): void
{
    Schema::dropIfExists('audit_logs');
}
```

---

### 4.7 Audit log — what gets captured

Every `created`, `updated`, and `deleted` event on Patient, PatientClinical,
and Insurance records. Only changed fields are included in `before`/`after`.

Example log entry for an updated patient:
```json
{
  "user_id": 1,
  "user_name": "radu",
  "action": "updated",
  "model_type": "Patient",
  "model_id": "142",
  "before": { "tel": "0712345678" },
  "after":  { "tel": "0799999999" },
  "ip_address": "192.168.1.10",
  "user_agent": "Mozilla/5.0 ...",
  "created_at": "2026-03-14 09:45:22"
}
```

`before`/`after` store **decrypted** plaintext — the audit log's purpose is
human-readable accountability. The `audit_logs` table has no encryption on its
own columns; its protection is the standard database access control.

---

### 4.8 Verification (Tinker — Step 8)

```php
// 1. Model reads correctly (decrypted):
$p = Patient::find(1);
$p->tel;            // "0712345678"  ← plaintext
$p->date_of_birth;  // "1985-04-23"  ← plaintext Y-m-d
$p->age;            //  40           ← Carbon calculated correctly

// 2. Raw DB is unreadable:
$raw = DB::table('patients')->where('patient_id', 1)->first();
$raw->tel;          // "eyJpdiI6Ii..."  ← AES-256 ciphertext

// 3. Clinical record:
$c = PatientClinical::find(1);
$c->description;    // plaintext
DB::table('patients_clinical')->where('patient_clinic_id', 1)->value('description');
// "eyJpdiI6Ii..."  ← ciphertext

// 4. Audit log:
DB::table('audit_logs')->latest()->first();
// shows the most recent change with who/what/when/where
```

---

### 4.9 Risk register

| Risk | Mitigation |
|---|---|
| Model casts applied before `encrypt:existing` runs → `DecryptException` | Strict step order; Steps 3–5 only after Step 2 confirms success |
| `insurance_no` INT column rejects encrypted string | Migration A (INT→TEXT) runs before `encrypt:existing` |
| `name` search broken by encryption | `name` is explicitly excluded; blind index deferred |
| Observer fires during `encrypt:existing`, logging garbage | Observers suppressed inside the command via `Model::withoutEvents()` |
| `date_of_birth` / Carbon age broken | Verified safe — see Section 3 |
| Partner clinic DB on different key | Both databases share same `APP_KEY` — verified |
| APP_KEY lost after implementation | Key must be backed up outside the repo independently |

---

*End of changeset document.*
*No implementation changes have been made to any source file at time of writing.*

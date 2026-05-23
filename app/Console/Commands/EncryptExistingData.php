<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class EncryptExistingData extends Command
{
    protected $signature = 'encrypt:existing
                            {--connection= : Database connection to use (default: DB_CONNECTION)}
                            {--database=   : Override the database name on the connection (bypasses cached config)}';

    protected $description = 'Encrypt all existing plaintext values in patients, patients_clinical, and insurance tables.';

    // Fields to encrypt per table.
    // date_of_birth is handled separately (Y-m-d → encrypt, d/m/Y → convert then encrypt).
    private const PATIENT_FIELDS = [
        'date_of_birth', 'tel', 'email', 'gender',
        'location', 'pobox', 'town', 'occupation', 'remarks',
    ];

    private const CLINICAL_FIELDS = [
        'diagnostic', 'description', 'tooth',
        'amount', 'paid', 'balance',
        'estimate_description', 'estimate', 'estimate_cost',
        'estimate_paid', 'estimate_balance',
        'notes', 'remarks',
    ];

    private const INSURANCE_FIELDS = [
        'insurance_no', 'insurance_id_no', 'insurance_provider',
        'insurance_remarks', 'invalidation_reason',
    ];

    public function handle(): int
    {
        $connection = $this->option('connection') ?: config('database.default');

        if ($dbName = $this->option('database')) {
            config(["database.connections.{$connection}.database" => $dbName]);
            DB::purge($connection);
        }

        $db = DB::connection($connection);

        $this->info("Using connection: {$connection}" . ($dbName ?? false ? " (database: {$dbName})" : ''));
        $this->newLine();

        $this->encryptTable($db, 'patients',          'patient_id',        self::PATIENT_FIELDS);
        $this->encryptTable($db, 'patients_clinical', 'patient_clinic_id', self::CLINICAL_FIELDS);
        $this->encryptTable($db, 'insurance',         'InsuranceID',       self::INSURANCE_FIELDS);

        $this->newLine();
        $this->info('Done. All existing rows have been encrypted.');
        $this->warn('Next step: update model $casts (Steps 3-5 in ENCRYPTION_CHANGESET.md).');

        return Command::SUCCESS;
    }

    private function encryptTable($db, string $table, string $pk, array $fields): void
    {
        $this->info("Encrypting table: {$table}");

        $rows = $db->table($table)->get(array_merge([$pk], $fields));
        $bar  = $this->output->createProgressBar($rows->count());
        $bar->start();

        foreach ($rows as $row) {
            $updates = [];

            foreach ($fields as $field) {
                $raw = $row->$field ?? null;

                if ($raw === null) {
                    continue; // leave NULL unchanged
                }

                if ($raw === '') {
                    // Laravel's encrypted cast cannot handle empty strings — normalise to NULL
                    $updates[$field] = null;
                    continue;
                }

                // Skip values that look already encrypted (base64 JSON payload from Crypt)
                if ($this->isAlreadyEncrypted($raw)) {
                    continue;
                }

                $updates[$field] = Crypt::encryptString((string) $raw);
            }

            if (!empty($updates)) {
                $db->table($table)->where($pk, $row->$pk)->update($updates);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    /**
     * A Laravel-encrypted payload is a JSON string with 'iv', 'value', 'mac' keys,
     * base64-encoded. Quick heuristic: it starts with 'eyJ' (base64 of '{"').
     */
    private function isAlreadyEncrypted(string $value): bool
    {
        return str_starts_with($value, 'eyJ');
    }
}

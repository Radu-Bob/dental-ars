<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class DecryptExistingData extends Command
{
    protected $signature = 'decrypt:existing
                            {--connection= : Database connection to use (default: DB_CONNECTION)}
                            {--database=   : Override the database name on the connection (bypasses cached config)}
                            {--force        : Skip confirmation prompt}';

    protected $description = 'Reverse encryption on patients, patients_clinical, and insurance tables (rollback tool).';

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

        if (!$this->option('force')) {
            $confirmed = $this->confirm(
                "This will decrypt ALL encrypted values in the [{$connection}] database. " .
                "Only run this as a rollback step. Continue?"
            );

            if (!$confirmed) {
                $this->info('Aborted.');
                return Command::SUCCESS;
            }
        }

        if ($dbName = $this->option('database')) {
            config(["database.connections.{$connection}.database" => $dbName]);
            DB::purge($connection);
        }

        $db = DB::connection($connection);
        $this->info("Using connection: {$connection}" . ($dbName ?? false ? " (database: {$dbName})" : ''));
        $this->newLine();

        $this->decryptTable($db, 'patients',          'patient_id',        self::PATIENT_FIELDS);
        $this->decryptTable($db, 'patients_clinical', 'patient_clinic_id', self::CLINICAL_FIELDS);
        $this->decryptTable($db, 'insurance',         'InsuranceID',       self::INSURANCE_FIELDS);

        $this->newLine();
        $this->info('Done. All rows have been restored to plaintext.');
        $this->warn('Next step: run `git checkout pre-ebcryption-stable` to restore source files.');

        return Command::SUCCESS;
    }

    private function decryptTable($db, string $table, string $pk, array $fields): void
    {
        $this->info("Decrypting table: {$table}");

        $rows = $db->table($table)->get(array_merge([$pk], $fields));
        $bar  = $this->output->createProgressBar($rows->count());
        $bar->start();

        foreach ($rows as $row) {
            $updates = [];

            foreach ($fields as $field) {
                $raw = $row->$field ?? null;

                if ($raw === null || $raw === '') {
                    continue;
                }

                // Only attempt to decrypt values that look like Crypt payloads
                if (!str_starts_with($raw, 'eyJ')) {
                    continue;
                }

                try {
                    $updates[$field] = Crypt::decryptString($raw);
                } catch (\Exception) {
                    // Value could not be decrypted — leave it untouched
                }
            }

            if (!empty($updates)) {
                $db->table($table)->where($pk, $row->$pk)->update($updates);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }
}

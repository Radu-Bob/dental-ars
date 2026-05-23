<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Read all existing plaintext JSON records before changing column type
        $existing = DB::table('audit_logs')
            ->select('id', 'before', 'after')
            ->where(function ($q) {
                $q->whereNotNull('before')->orWhereNotNull('after');
            })
            ->get();

        // Change column types from json → longtext to accept encrypted strings
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->longText('before')->nullable()->change();
            $table->longText('after')->nullable()->change();
        });

        // Re-encrypt each existing record
        foreach ($existing as $row) {
            $updates = [];

            if ($row->before !== null) {
                // before is a valid JSON string — encrypt it as-is
                $updates['before'] = Crypt::encryptString($row->before);
            }

            if ($row->after !== null) {
                $updates['after'] = Crypt::encryptString($row->after);
            }

            if (!empty($updates)) {
                DB::table('audit_logs')->where('id', $row->id)->update($updates);
            }
        }
    }

    public function down(): void
    {
        // Decrypt back to plain JSON, then restore json column type
        $existing = DB::table('audit_logs')
            ->select('id', 'before', 'after')
            ->where(function ($q) {
                $q->whereNotNull('before')->orWhereNotNull('after');
            })
            ->get();

        Schema::table('audit_logs', function (Blueprint $table) {
            // Temporarily use text so we can write the JSON strings back
            $table->text('before')->nullable()->change();
            $table->text('after')->nullable()->change();
        });

        foreach ($existing as $row) {
            $updates = [];

            if ($row->before !== null) {
                try {
                    $updates['before'] = Crypt::decryptString($row->before);
                } catch (\Exception) {
                    $updates['before'] = $row->before; // already plain
                }
            }

            if ($row->after !== null) {
                try {
                    $updates['after'] = Crypt::decryptString($row->after);
                } catch (\Exception) {
                    $updates['after'] = $row->after;
                }
            }

            if (!empty($updates)) {
                DB::table('audit_logs')->where('id', $row->id)->update($updates);
            }
        }
    }
};

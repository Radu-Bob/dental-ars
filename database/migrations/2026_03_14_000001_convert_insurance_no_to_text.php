<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Disable strict mode for this statement only — the insurance table contains
        // legacy '0000-00-00 00:00:00' datetime values that MySQL strict mode rejects
        // during any ALTER TABLE, even when we're only changing a different column.
        DB::statement("SET SESSION sql_mode = ''");

        Schema::table('insurance', function (Blueprint $table) {
            $table->text('insurance_no')->nullable()->change();
        });

        // Restore default modes for the rest of the request lifecycle
        DB::statement("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
    }

    public function down(): void
    {
        // WARNING: run `php artisan decrypt:existing` before rolling back this migration.
        // Rolling back while the column holds encrypted ciphertext will corrupt the data.
        Schema::table('insurance', function (Blueprint $table) {
            $table->integer('insurance_no')->nullable()->change();
        });
    }
};

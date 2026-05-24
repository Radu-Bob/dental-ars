<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('patients_clinical', 'insurance_provider_id')) {
            Schema::table('patients_clinical', function (Blueprint $table) {
                $table->integer('insurance_provider_id')->nullable()->after('is_insurance_claim');
            });
        }
    }

    public function down(): void
    {
        Schema::table('patients_clinical', function (Blueprint $table) {
            $table->dropColumn('insurance_provider_id');
        });
    }
};

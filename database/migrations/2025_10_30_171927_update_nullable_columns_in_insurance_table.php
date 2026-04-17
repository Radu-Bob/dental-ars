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
        Schema::table('insurance', function (Blueprint $table) {
            // Change these to allow NULL
            $table->string('ver_acc_no', 50)->nullable()->change();
            $table->integer('insurance_no')->nullable()->change();
            $table->text('insurance_provider')->nullable()->change();
            $table->text('insurance_remarks')->nullable()->change();
            
            // Note: 'insurance_id_no' is already TEXT and allows NULL, so we leave it.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('insurance', function (Blueprint $table) {
            // Revert these columns to NOT NULL (if you can, which depends on data)
            $table->string('ver_acc_no', 50)->nullable(false)->change();
            $table->integer('insurance_no')->nullable(false)->change();
            $table->text('insurance_provider')->nullable(false)->change();
            $table->text('insurance_remarks')->nullable(false)->change();
        });
    }
};

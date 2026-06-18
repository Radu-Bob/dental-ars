<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->integer('patient_id')->nullable();
            $table->foreign('patient_id')->references('patient_id')->on('patients');
            $table->string('patient_name_freetext')->nullable();
            $table->date('appointment_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('status')->default('scheduled');
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};

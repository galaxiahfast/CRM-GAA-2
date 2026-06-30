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
        Schema::create('device_attendance_logs', function (Blueprint $table) {
            $table->string('employee_id', 50);
            $table->string('person_name', 255)->nullable();
            $table->dateTime('auth_datetime');
            $table->date('auth_date')->nullable();
            $table->time('auth_time')->nullable();
            $table->string('direction', 10)->nullable(); // IN o OUT
            $table->string('device_name', 255)->nullable();

            // Llave primaria compuesta para evitar registros duplicados
            $table->primary(['employee_id', 'auth_datetime']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_attendance_logs');
    }
};
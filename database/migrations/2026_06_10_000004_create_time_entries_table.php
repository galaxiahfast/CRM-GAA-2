<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_entries', function (Blueprint $table) {
            $table->id();

            // Restricción referencial (regla 8.7): no se puede eliminar un
            // usuario, cliente o actividad con registros de tiempo asociados.
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('sub_service_id')->constrained('sub_services')->restrictOnDelete();

            // Fotografía del contexto organizacional al momento del registro.
            $table->foreignId('role_id_snapshot')->constrained('roles')->restrictOnDelete();
            $table->foreignId('job_position_id_snapshot')->constrained('job_positions')->restrictOnDelete();
            $table->foreignId('physical_area_id_snapshot')->constrained('physical_areas')->restrictOnDelete();

            $table->date('entry_date');
            // 0=En progreso, 1=Pausada, 2=Finalizada, 3=Cerrada automáticamente
            $table->unsignedTinyInteger('status')->default(0);
            $table->unsignedInteger('total_duration_seconds')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('entry_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_entries');
    }
};

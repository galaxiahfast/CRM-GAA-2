<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lo que el Cronómetro del CRM ya envió a ManagerControl: una fila por usuario, día, cliente y actividad.
 * Sirve para no duplicar capturas y para actualizar solo los registros que el CRM creó y que nadie editó después
 * en ManagerControl (lo editado allá se respeta: el envío es solo del Cronómetro hacia ManagerControl).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('managercontrol_timer_syncs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('work_date');
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('sub_service_id');
            $table->unsignedBigInteger('legacy_employee_id')->nullable();
            $table->unsignedBigInteger('legacy_customer_id')->nullable();
            $table->unsignedBigInteger('legacy_activity_id')->nullable();
            $table->unsignedBigInteger('legacy_record_id')->nullable();
            $table->unsignedInteger('sent_seconds')->default(0);
            $table->decimal('sent_hours', 5, 1)->nullable();
            $table->string('sent_description', 255)->nullable();
            // synced: enviado | edited: editado o dado de baja en ManagerControl (ya no se toca) | skipped | error
            $table->string('status', 20);
            $table->string('message', 500)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'work_date', 'customer_id', 'sub_service_id'], 'mc_timer_sync_unique');
            $table->index(['status', 'work_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('managercontrol_timer_syncs');
    }
};

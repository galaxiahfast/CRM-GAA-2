<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Asignación manual: usuario del CRM ↔ colaborador de ManagerControl (solo se guarda en la base del CRM). */
    public function up(): void
    {
        Schema::create('managercontrol_employee_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('legacy_employee_id')->unique();
            $table->string('legacy_employee_name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('managercontrol_employee_links');
    }
};

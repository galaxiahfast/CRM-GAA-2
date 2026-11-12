<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Bitácora (en la base del CRM) de cada alta, edición o baja hecha desde el CRM en ManagerControl. */
    public function up(): void
    {
        Schema::create('managercontrol_audits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 20);
            $table->unsignedBigInteger('legacy_record_id');
            $table->unsignedInteger('legacy_employee_id');
            $table->json('payload')->nullable();
            $table->timestamps();
            $table->index(['legacy_employee_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('managercontrol_audits');
    }
};

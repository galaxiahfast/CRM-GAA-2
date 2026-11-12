<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('managercontrol_catalog_links', function (Blueprint $table): void {
            $table->id();
            $table->string('kind', 20);
            $table->unsignedBigInteger('crm_id');
            $table->unsignedInteger('company_id');
            $table->unsignedInteger('exercise_id');
            $table->unsignedBigInteger('legacy_id');
            $table->string('origin', 20); // imported o matched
            $table->timestamps();
            $table->unique(['kind', 'crm_id', 'company_id', 'exercise_id'], 'mc_catalog_local_unique');
            $table->index(['kind', 'legacy_id', 'company_id', 'exercise_id'], 'mc_catalog_remote_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('managercontrol_catalog_links');
    }
};

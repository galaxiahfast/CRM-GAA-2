<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_entry_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('time_entry_id')->constrained('time_entries')->cascadeOnDelete();
            $table->foreignId('admin_id')->constrained('users')->restrictOnDelete();
            $table->json('old_values');
            $table->json('new_values');
            $table->text('reason');
            $table->timestamps();

            $table->index('time_entry_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_entry_audits');
    }
};

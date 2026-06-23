<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_intervals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('time_entry_id')->constrained('time_entries')->cascadeOnDelete();
            $table->dateTime('started_at');
            $table->dateTime('ended_at')->nullable();
            $table->timestamps();

            $table->index('time_entry_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_intervals');
    }
};

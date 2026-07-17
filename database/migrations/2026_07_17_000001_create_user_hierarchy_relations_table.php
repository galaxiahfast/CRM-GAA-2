<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_hierarchy_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subordinate_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('superior_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('job_position_id')->nullable()->constrained('job_positions')->nullOnDelete();
            $table->foreignId('physical_area_id')->nullable()->constrained('physical_areas')->nullOnDelete();
            $table->timestamps();

            $table->unique(['subordinate_id', 'superior_id']);
            $table->index('superior_id');
            $table->index('subordinate_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_hierarchy_relations');
    }
};

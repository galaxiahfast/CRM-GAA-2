<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('technical_support_reports', function (Blueprint $table) {
            $table->id();
            $table->string('folio')->unique();
            $table->timestamp('issued_at');
            $table->string('responsible');
            $table->json('supports');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        DB::table('technical_support_reports')->insert([
            'folio' => 'ST-20261022-0001',
            'issued_at' => '2026-10-22 17:51:46',
            'responsible' => 'Julian Emiliano Ortiz Rivero',
            'supports' => json_encode([
                ['key' => 'initial_support', 'title' => 'Soporte técnico general'],
            ], JSON_UNESCAPED_UNICODE),
            'created_by' => null,
            'created_at' => '2026-10-22 17:51:46',
            'updated_at' => '2026-10-22 17:51:46',
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('technical_support_reports');
    }
};

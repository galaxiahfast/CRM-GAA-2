<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_catalog_index', function (Blueprint $table): void {
            $table->id();
            $table->string('normalized_key', 255)->unique();
            $table->string('brand', 100)->default('')->index();
            $table->string('model', 120)->index();
            $table->string('equipment_type', 80)->default('Otro')->index();
            $table->string('serial_number', 100)->nullable()->index();
            $table->json('typical_accessories')->nullable();
            $table->json('aliases')->nullable();
            $table->unsignedInteger('usage_count')->default(0)->index();
            $table->text('search_text');
            $table->timestamps();
        });

        Schema::table('ordenes_servicio', function (Blueprint $table): void {
            $table->longText('ocr_raw_text')->nullable()->after('foto_path');
        });
    }

    public function down(): void
    {
        Schema::table('ordenes_servicio', function (Blueprint $table): void {
            $table->dropColumn('ocr_raw_text');
        });
        Schema::dropIfExists('equipment_catalog_index');
    }
};

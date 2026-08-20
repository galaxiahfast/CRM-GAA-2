<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_chat_messages', function (Blueprint $table): void {
            $table->string('image_path', 2048)->nullable()->after('message');
            $table->string('image_original_name')->nullable()->after('image_path');
        });
    }

    public function down(): void
    {
        Schema::table('support_chat_messages', function (Blueprint $table): void {
            $table->dropColumn(['image_path', 'image_original_name']);
        });
    }
};

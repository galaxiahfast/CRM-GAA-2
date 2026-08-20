<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_chat_messages', function (Blueprint $table): void {
            $table->string('attachment_path', 2048)->nullable()->after('image_original_name');
            $table->string('attachment_original_name')->nullable()->after('attachment_path');
            $table->string('attachment_mime', 120)->nullable()->after('attachment_original_name');
            $table->unsignedBigInteger('attachment_size')->nullable()->after('attachment_mime');
            $table->string('sticker_key', 50)->nullable()->after('attachment_size');
        });
    }

    public function down(): void
    {
        Schema::table('support_chat_messages', function (Blueprint $table): void {
            $table->dropColumn([
                'attachment_path',
                'attachment_original_name',
                'attachment_mime',
                'attachment_size',
                'sticker_key',
            ]);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Hasta 10 archivos por mensaje: lista de {path, name, mime, size, kind} (los mensajes anteriores siguen con sus columnas). */
    public function up(): void
    {
        Schema::table('support_chat_messages', function (Blueprint $table): void {
            $table->json('attachments')->nullable()->after('attachment_size');
        });
    }

    public function down(): void
    {
        Schema::table('support_chat_messages', function (Blueprint $table): void {
            $table->dropColumn('attachments');
        });
    }
};

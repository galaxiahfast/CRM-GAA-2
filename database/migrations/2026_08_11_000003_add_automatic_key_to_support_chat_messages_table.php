<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_chat_messages', function (Blueprint $table): void {
            $table->string('automatic_key')->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('support_chat_messages', function (Blueprint $table): void {
            $table->dropUnique(['automatic_key']);
            $table->dropColumn('automatic_key');
        });
    }
};

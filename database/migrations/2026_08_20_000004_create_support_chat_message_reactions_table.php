<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_chat_message_reactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('support_chat_message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reaction', 20);
            $table->timestamps();

            $table->unique(['support_chat_message_id', 'user_id'], 'support_message_user_reaction_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_chat_message_reactions');
    }
};

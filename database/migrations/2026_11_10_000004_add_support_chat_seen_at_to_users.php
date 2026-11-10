<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('support_chat_seen_at')->nullable();
        });

        // Quien ya existe empieza sin mensajes pendientes.
        \Illuminate\Support\Facades\DB::table('users')->update(['support_chat_seen_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('support_chat_seen_at');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'last_seen_section')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->string('last_seen_section', 80)->nullable();
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'last_seen_section')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('last_seen_section');
            });
        }
    }
};

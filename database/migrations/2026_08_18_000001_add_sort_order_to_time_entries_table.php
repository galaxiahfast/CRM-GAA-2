<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('time_entries', function (Blueprint $table): void {
            $table->unsignedInteger('sort_order')->default(0)->after('status');
            $table->index(['user_id', 'entry_date', 'sort_order'], 'time_entries_user_date_sort_index');
        });
    }

    public function down(): void
    {
        Schema::table('time_entries', function (Blueprint $table): void {
            $table->dropIndex('time_entries_user_date_sort_index');
            $table->dropColumn('sort_order');
        });
    }
};

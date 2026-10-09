<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('backup_uploads', function (Blueprint $table): void {
            $table->foreignId('customer_id')->nullable()->change();
            $table->string('category', 10)->nullable()->change();
            $table->json('manifest')->nullable()->after('storage_path');
        });
    }

    public function down(): void
    {
        DB::table('backup_uploads')->whereNull('customer_id')->delete();

        Schema::table('backup_uploads', function (Blueprint $table): void {
            $table->dropColumn('manifest');
            $table->foreignId('customer_id')->nullable(false)->change();
            $table->string('category', 10)->nullable(false)->change();
        });
    }
};

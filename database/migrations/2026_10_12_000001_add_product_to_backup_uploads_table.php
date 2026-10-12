<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('backup_uploads', function (Blueprint $table): void {
            $table->string('product', 20)->default('contabilidad')->after('site');
            $table->index(['site', 'product', 'status'], 'backup_upload_site_product_status');
        });
    }

    public function down(): void
    {
        Schema::table('backup_uploads', function (Blueprint $table): void {
            $table->dropIndex('backup_upload_site_product_status');
            $table->dropColumn('product');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('backup_uploads', function (Blueprint $table): void {
            $table->string('assigned_customer')->nullable()->after('manifest');
            $table->text('notes')->nullable()->after('assigned_customer');
            $table->uuid('supersedes_upload_id')->nullable()->after('notes')->index();
            $table->timestamp('superseded_at')->nullable()->after('completed_at')->index();
        });
    }

    public function down(): void
    {
        Schema::table('backup_uploads', function (Blueprint $table): void {
            $table->dropIndex(['supersedes_upload_id']);
            $table->dropIndex(['superseded_at']);
            $table->dropColumn(['assigned_customer', 'notes', 'supersedes_upload_id', 'superseded_at']);
        });
    }
};

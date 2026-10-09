<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backup_uploads', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('upload_key', 64)->index();
            $table->string('site', 20)->index();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('category', 10);
            $table->string('original_name');
            $table->string('extension', 10);
            $table->string('mime_type', 150)->nullable();
            $table->unsignedBigInteger('size');
            $table->unsignedInteger('chunk_size');
            $table->unsignedInteger('total_chunks');
            $table->json('uploaded_chunks')->nullable();
            $table->unsignedBigInteger('received_bytes')->default(0);
            $table->string('status', 20)->index();
            $table->string('storage_path')->nullable();
            $table->string('checksum', 64)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('last_activity_at')->nullable()->index();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['site', 'customer_id', 'status'], 'backup_upload_site_customer_status');
            $table->index(['created_at', 'id'], 'backup_upload_created_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_uploads');
    }
};

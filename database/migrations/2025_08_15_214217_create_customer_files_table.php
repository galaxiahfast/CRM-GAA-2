<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customer_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('sub_service_id')->constrained('sub_services')->onDelete('cascade');
            $table->string('file_path');
            $table->string('original_name');
            $table->boolean('file_type');
            $table->boolean('declaration_type');
            $table->foreignId('statement_id')
                ->nullable()
                ->constrained('statements')
                ->onDelete('cascade')
                ->after('customer_id');
            $table->foreignId('state_id')
                ->nullable()
                ->constrained('states')
                ->onDelete('cascade')
                ->after('statement_id');
            $table->date('upload_period')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_files');
    }
};

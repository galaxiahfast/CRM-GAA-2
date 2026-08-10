<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sessions', function (Blueprint $table): void {
            $table->index(['user_id', 'last_activity'], 'sessions_user_activity_index');
        });

        Schema::table('notifications', function (Blueprint $table): void {
            $table->index(
                ['notifiable_type', 'notifiable_id', 'created_at'],
                'notifications_notifiable_created_index'
            );
            $table->index(
                ['notifiable_type', 'notifiable_id', 'read_at'],
                'notifications_notifiable_read_index'
            );
        });

        Schema::table('time_entries', function (Blueprint $table): void {
            $table->index(['user_id', 'entry_date'], 'time_entries_user_date_index');
        });

        Schema::table('control_de_horas', function (Blueprint $table): void {
            $table->index(
                ['employeeID', 'authDate', 'authDateTime'],
                'control_hours_employee_date_index'
            );
        });

        Schema::table('customer_accountants', function (Blueprint $table): void {
            $table->index(
                ['accountant_id', 'status', 'customer_id'],
                'customer_accountants_accountant_status_index'
            );
            $table->index(
                ['customer_id', 'status', 'accountant_id'],
                'customer_accountants_customer_status_index'
            );
        });

        Schema::table('customer_files', function (Blueprint $table): void {
            $table->index(
                ['customer_id', 'upload_period', 'sub_service_id'],
                'customer_files_period_service_index'
            );
            $table->index(
                ['customer_id', 'declaration_type', 'file_type'],
                'customer_files_declaration_type_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('customer_files', function (Blueprint $table): void {
            $table->dropIndex('customer_files_period_service_index');
            $table->dropIndex('customer_files_declaration_type_index');
        });

        Schema::table('customer_accountants', function (Blueprint $table): void {
            $table->dropIndex('customer_accountants_accountant_status_index');
            $table->dropIndex('customer_accountants_customer_status_index');
        });

        Schema::table('control_de_horas', function (Blueprint $table): void {
            $table->dropIndex('control_hours_employee_date_index');
        });

        Schema::table('time_entries', function (Blueprint $table): void {
            $table->dropIndex('time_entries_user_date_index');
        });

        Schema::table('notifications', function (Blueprint $table): void {
            $table->dropIndex('notifications_notifiable_created_index');
            $table->dropIndex('notifications_notifiable_read_index');
        });

        Schema::table('sessions', function (Blueprint $table): void {
            $table->dropIndex('sessions_user_activity_index');
        });
    }
};

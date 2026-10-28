<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('technical_support_report_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('technical_support_report_id')->nullable();
            $table->foreign('technical_support_report_id', 'tsr_movements_report_fk')
                ->references('id')
                ->on('technical_support_reports')
                ->nullOnDelete();
            $table->string('folio')->index();
            $table->string('action', 30)->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_name')->nullable();
            $table->string('user_email')->nullable();
            $table->json('report_snapshot')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();
        });

        $users = DB::table('users')->get(['id', 'name', 'email'])->keyBy('id');
        $now = now();

        DB::table('technical_support_reports')
            ->orderBy('id')
            ->get()
            ->each(function ($report) use ($users, $now): void {
                $user = $report->created_by ? $users->get($report->created_by) : null;

                DB::table('technical_support_report_movements')->insert([
                    'technical_support_report_id' => $report->id,
                    'folio' => $report->folio,
                    'action' => 'created',
                    'user_id' => $report->created_by,
                    'user_name' => $user?->name,
                    'user_email' => $user?->email,
                    'report_snapshot' => json_encode($report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'occurred_at' => $report->created_at ?? $report->issued_at,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('technical_support_report_movements');
    }
};

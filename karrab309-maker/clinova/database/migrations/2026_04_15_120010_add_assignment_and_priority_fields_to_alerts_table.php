<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alerts', function (Blueprint $table) {
            $table
                ->foreignId('assigned_doctor_id')
                ->nullable()
                ->after('patient_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->enum('priority', ['normal', 'urgent'])->default('normal')->after('value');

            $table->timestamp('acknowledged_at')->nullable()->after('status');
            $table->timestamp('assigned_at')->nullable()->after('acknowledged_at');
            $table->timestamp('escalated_at')->nullable()->after('assigned_at');
            $table->unsignedInteger('reassigned_count')->default(0)->after('escalated_at');

            $table->index(['assigned_doctor_id', 'status', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::table('alerts', function (Blueprint $table) {
            $table->dropIndex(['assigned_doctor_id', 'status', 'priority']);
            $table->dropConstrainedForeignId('assigned_doctor_id');
            $table->dropColumn(['priority', 'acknowledged_at', 'assigned_at', 'escalated_at', 'reassigned_count']);
        });
    }
};


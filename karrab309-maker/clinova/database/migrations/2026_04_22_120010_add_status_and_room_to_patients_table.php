<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            if (!Schema::hasColumn('patients', 'status')) {
                $table->string('status')->default('admitted')->after('medical_history');
            }
            if (!Schema::hasColumn('patients', 'room_number')) {
                $table->string('room_number', 50)->nullable()->after('status');
            }
            if (!Schema::hasColumn('patients', 'bed_number')) {
                $table->string('bed_number', 50)->nullable()->after('room_number');
            }
            if (!Schema::hasColumn('patients', 'assigned_nurse_id')) {
                $table->foreignId('assigned_nurse_id')->nullable()->after('assigned_doctor_id')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            if (Schema::hasColumn('patients', 'assigned_nurse_id')) {
                $table->dropConstrainedForeignId('assigned_nurse_id');
            }
            $cols = array_filter(['bed_number', 'room_number', 'status'], fn ($c) => Schema::hasColumn('patients', $c));
            if ($cols) {
                $table->dropColumn($cols);
            }
        });
    }
};


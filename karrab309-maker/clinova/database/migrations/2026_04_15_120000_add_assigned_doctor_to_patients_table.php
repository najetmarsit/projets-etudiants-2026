<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table
                ->foreignId('assigned_doctor_id')
                ->nullable()
                ->after('user_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('assigned_at')->nullable()->after('assigned_doctor_id');

            $table->index(['assigned_doctor_id']);
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropIndex(['assigned_doctor_id']);
            $table->dropConstrainedForeignId('assigned_doctor_id');
            $table->dropColumn('assigned_at');
        });
    }
};


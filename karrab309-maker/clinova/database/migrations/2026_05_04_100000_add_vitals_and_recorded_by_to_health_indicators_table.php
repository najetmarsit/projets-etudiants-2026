<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('health_indicators', function (Blueprint $table) {
            $table->unsignedSmallInteger('heart_rate')->nullable()->after('patient_id');
            $table->decimal('blood_glucose', 5, 2)->nullable()->after('heart_rate');
            $table->unsignedSmallInteger('blood_pressure_systolic')->nullable()->after('blood_glucose');
            $table->unsignedSmallInteger('blood_pressure_diastolic')->nullable()->after('blood_pressure_systolic');
            $table->foreignId('recorded_by_user_id')->nullable()->after('image_path')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('health_indicators', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recorded_by_user_id');
            $table->dropColumn([
                'heart_rate',
                'blood_glucose',
                'blood_pressure_systolic',
                'blood_pressure_diastolic',
            ]);
        });
    }
};

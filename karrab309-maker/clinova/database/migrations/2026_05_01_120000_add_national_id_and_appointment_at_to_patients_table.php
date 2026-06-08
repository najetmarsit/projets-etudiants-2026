<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Alignement schéma / API / seeders : CIN (national_id), date RDV (appointment_at).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            if (! Schema::hasColumn('patients', 'national_id')) {
                $table->string('national_id', 32)->nullable()->unique()->after('birth_date');
            }
            if (! Schema::hasColumn('patients', 'appointment_at')) {
                $table->date('appointment_at')->nullable()->after('admission_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            if (Schema::hasColumn('patients', 'appointment_at')) {
                $table->dropColumn('appointment_at');
            }
            if (Schema::hasColumn('patients', 'national_id')) {
                $table->dropUnique(['national_id']);
                $table->dropColumn('national_id');
            }
        });
    }
};

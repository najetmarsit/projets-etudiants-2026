<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Commentaires détaillés du médecin sur l'état du patient (visible par le patient en temps réel).
     */
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->text('doctor_observations')->nullable()->after('medical_history');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn('doctor_observations');
        });
    }
};

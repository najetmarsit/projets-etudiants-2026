<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Champs complets pour la fiche patient (saisie médecin).
     * Nom, prénom, téléphone, adresse, diagnostic, maladie actuelle,
     * traitement prescrit, comptes rendus préop et postop.
     */
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('user_id');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('phone')->nullable()->after('gender');
            $table->text('address')->nullable()->after('phone');
            $table->text('diagnosis')->nullable()->after('medical_history');
            $table->text('current_illness')->nullable()->after('diagnosis');
            $table->text('prescribed_treatment')->nullable()->after('current_illness');
            $table->text('pre_op_report')->nullable()->after('doctor_observations');
            $table->text('post_op_report')->nullable()->after('pre_op_report');
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn([
                'first_name', 'last_name', 'phone', 'address',
                'diagnosis', 'current_illness', 'prescribed_treatment',
                'pre_op_report', 'post_op_report',
            ]);
        });
    }
};

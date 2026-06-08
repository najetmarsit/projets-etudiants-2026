<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Accélère les filtres LIKE sur liste patients (prénom / nom) et le périmètre médecin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = array_map(fn ($i) => $i->getName(), $sm->listTableIndexes('patients'));

            if (! in_array('patients_first_name_index', $indexes, true)) {
                $table->index('first_name');
            }
            if (! in_array('patients_last_name_index', $indexes, true)) {
                $table->index('last_name');
            }
            if (! in_array('patients_assigned_doctor_id_id_index', $indexes, true)) {
                $table->index(['assigned_doctor_id', 'id']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropIndex(['first_name']);
            $table->dropIndex(['last_name']);
            $table->dropIndex(['assigned_doctor_id', 'id']);
        });
    }
};

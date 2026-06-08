<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = array_map(fn ($i) => $i->getName(), $sm->listTableIndexes('patients'));

            if (!in_array('patients_user_id_index', $indexes, true)) {
                $table->index('user_id');
            }
            if (!in_array('patients_status_index', $indexes, true)) {
                $table->index('status');
            }
            if (!in_array('patients_national_id_index', $indexes, true)) {
                $table->index('national_id');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = array_map(fn ($i) => $i->getName(), $sm->listTableIndexes('users'));

            if (!in_array('users_role_index', $indexes, true)) {
                $table->index('role');
            }
        });

        Schema::table('health_indicators', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = array_map(fn ($i) => $i->getName(), $sm->listTableIndexes('health_indicators'));

            if (!in_array('health_indicators_patient_id_index', $indexes, true)) {
                $table->index('patient_id');
            }
            if (!in_array('health_indicators_recorded_by_index', $indexes, true)) {
                $table->index('recorded_by');
            }
        });

        Schema::table('operations', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = array_map(fn ($i) => $i->getName(), $sm->listTableIndexes('operations'));

            if (!in_array('operations_patient_id_index', $indexes, true)) {
                $table->index('patient_id');
            }
            if (!in_array('operations_doctor_id_index', $indexes, true)) {
                $table->index('doctor_id');
            }
        });

        Schema::table('notifications', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = array_map(fn ($i) => $i->getName(), $sm->listTableIndexes('notifications'));

            if (!in_array('notifications_channel_index', $indexes, true)) {
                $table->index('channel');
            }
            if (!in_array('notifications_audience_index', $indexes, true)) {
                $table->index('audience');
            }
            if (!in_array('notifications_recipient_user_id_index', $indexes, true)) {
                $table->index('recipient_user_id');
            }
        });

        Schema::table('alerts', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = array_map(fn ($i) => $i->getName(), $sm->listTableIndexes('alerts'));

            if (!in_array('alerts_patient_id_index', $indexes, true)) {
                $table->index('patient_id');
            }
            if (!in_array('alerts_status_index', $indexes, true)) {
                $table->index('status');
            }
        });

        Schema::table('messages', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = array_map(fn ($i) => $i->getName(), $sm->listTableIndexes('messages'));

            if (!in_array('messages_sender_id_index', $indexes, true)) {
                $table->index('sender_id');
            }
            if (!in_array('messages_receiver_id_index', $indexes, true)) {
                $table->index('receiver_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['national_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
        });

        Schema::table('health_indicators', function (Blueprint $table) {
            $table->dropIndex(['patient_id']);
            $table->dropIndex(['recorded_by']);
        });

        Schema::table('operations', function (Blueprint $table) {
            $table->dropIndex(['patient_id']);
            $table->dropIndex(['doctor_id']);
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['channel']);
            $table->dropIndex(['audience']);
            $table->dropIndex(['recipient_user_id']);
        });

        Schema::table('alerts', function (Blueprint $table) {
            $table->dropIndex(['patient_id']);
            $table->dropIndex(['status']);
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex(['sender_id']);
            $table->dropIndex(['receiver_id']);
        });
    }
};

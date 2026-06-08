<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();

            // Optionnel : rattacher à un patient (notification liée à une prise en charge)
            $table->foreignId('patient_id')->nullable()->constrained('patients')->nullOnDelete();

            /**
             * Deux modes de routage :
             * - broadcast par audience (ex: tous les Doctors voient l’inbox Doctor)
             * - ciblage d’un utilisateur précis (recipient_user_id)
             */
            $table->string('audience'); // admin|doctor|nurse|laboratory|accountant|patient
            $table->foreignId('recipient_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('channel'); // staff_web|patient_mobile
            $table->string('type');    // ex: patient.arrival_urgent, patient.status_changed, lab.result_ready...
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('priority')->default('normal'); // normal|urgent
            $table->json('data')->nullable();

            $table->timestamp('read_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();

            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['audience', 'channel', 'created_at']);
            $table->index(['recipient_user_id', 'read_at']);
            $table->index(['patient_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};


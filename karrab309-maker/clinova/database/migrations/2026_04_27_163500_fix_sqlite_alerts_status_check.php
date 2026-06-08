<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SQLite : enum() crée un CHECK (status IN (...)).
 * La table alerts a été créée initialement avec status = ('new','acknowledged'), ce qui bloque
 * nos nouveaux statuts (sent, escalated, ...). On recrée la table avec status en string.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            return;
        }

        if (! Schema::hasTable('alerts')) {
            return;
        }

        Schema::disableForeignKeyConstraints();
        DB::statement('PRAGMA foreign_keys=OFF');

        Schema::dropIfExists('alerts_new_status_fix');

        Schema::create('alerts_new_status_fix', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');

            $table->foreignId('assigned_doctor_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('indicator_type');
            $table->string('value');
            $table->string('priority', 32)->default('normal');
            $table->text('message');

            // IMPORTANT: string => pas de CHECK restrictif
            $table->string('status', 32)->default('sent');

            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('escalated_at')->nullable();
            $table->unsignedInteger('reassigned_count')->default(0);

            $table->timestamps();

            $table->index(['assigned_doctor_id', 'status', 'priority']);
        });

        // Copier les données existantes
        $rows = DB::table('alerts')->get();
        foreach ($rows as $r) {
            $status = (string) ($r->status ?? 'sent');
            if ($status === 'new') {
                $status = 'sent';
            }

            DB::table('alerts_new_status_fix')->insert([
                'id' => $r->id,
                'patient_id' => $r->patient_id,
                'assigned_doctor_id' => $r->assigned_doctor_id,
                'indicator_type' => $r->indicator_type,
                'value' => $r->value,
                'priority' => $r->priority ?? 'normal',
                'message' => $r->message,
                'status' => $status,
                'acknowledged_at' => $r->acknowledged_at,
                'assigned_at' => $r->assigned_at,
                'escalated_at' => $r->escalated_at,
                'reassigned_count' => $r->reassigned_count ?? 0,
                'created_at' => $r->created_at,
                'updated_at' => $r->updated_at,
            ]);
        }

        Schema::drop('alerts');
        Schema::rename('alerts_new_status_fix', 'alerts');

        DB::statement('PRAGMA foreign_keys=ON');
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // No-op (best-effort). On ne réintroduit pas un CHECK restrictif.
    }
};


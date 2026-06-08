<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Convertir les anciennes valeurs si existantes
        DB::table('alerts')->where('status', 'new')->update(['status' => 'sent']);

        // 2) Étendre l'enum MySQL (XAMPP)
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement(
                "ALTER TABLE alerts MODIFY COLUMN status " .
                "ENUM('sent','acknowledged','in_progress','resolved','escalated','expired','cancelled') " .
                "NOT NULL DEFAULT 'sent'"
            );
        }
        // Pour sqlite/pgsql : on ne force pas ici un type enum (selon env). Les valeurs seront quand même mises à jour.
    }

    public function down(): void
    {
        // Revenir à l'ancien enum MySQL (best-effort)
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            // Remapper les valeurs "sent" vers "new" pour respecter l'ancien enum
            DB::table('alerts')->where('status', 'sent')->update(['status' => 'new']);
            DB::table('alerts')->whereNotIn('status', ['new', 'acknowledged'])->update(['status' => 'acknowledged']);

            DB::statement(
                "ALTER TABLE alerts MODIFY COLUMN status ENUM('new','acknowledged') NOT NULL DEFAULT 'new'"
            );
        } else {
            // best-effort
            DB::table('alerts')->where('status', 'sent')->update(['status' => 'new']);
        }
    }
};


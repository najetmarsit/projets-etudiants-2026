<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('Admin','Doctor','Patient','Laboratory') NOT NULL DEFAULT 'Patient'");
        }
        // SQLite : corrigé par la migration 2026_04_10_000000_fix_sqlite_users_role_laboratory_check
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('Admin','Doctor','Patient') NOT NULL DEFAULT 'Patient'");
        }
    }
};

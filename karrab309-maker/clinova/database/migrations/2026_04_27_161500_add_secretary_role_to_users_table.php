<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite : role est déjà string (voir migration de fix), rien à faire.
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(
            "ALTER TABLE users MODIFY COLUMN role " .
            "ENUM('Admin','Doctor','Nurse','Secretary','Patient','Laboratory','Accountant') " .
            "NOT NULL DEFAULT 'Patient'"
        );
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(
            "ALTER TABLE users MODIFY COLUMN role " .
            "ENUM('Admin','Doctor','Nurse','Patient','Laboratory','Accountant') " .
            "NOT NULL DEFAULT 'Patient'"
        );
    }
};


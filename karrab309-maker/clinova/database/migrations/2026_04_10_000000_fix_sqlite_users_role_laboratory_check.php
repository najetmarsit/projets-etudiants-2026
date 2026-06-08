<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SQLite : Laravel mappe enum() vers VARCHAR + CHECK (role IN ('Admin','Doctor','Patient')).
 * Insérer « Laboratory » viole cette contrainte. On recrée la table avec role en string sans CHECK restreint.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            return;
        }

        Schema::dropIfExists('users_new_role_fix');

        // État cassé possible après une première tentative (rename sans recréation réussie)
        if (Schema::hasTable('users_old_role_fix') && Schema::hasTable('users')) {
            Schema::drop('users_old_role_fix');
        }

        $sourceTable = 'users';
        if (! Schema::hasTable('users') && Schema::hasTable('users_old_role_fix')) {
            $sourceTable = 'users_old_role_fix';
        } elseif (! Schema::hasTable('users')) {
            return;
        }

        Schema::disableForeignKeyConstraints();
        DB::statement('PRAGMA foreign_keys=OFF');

        Schema::create('users_new_role_fix', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role')->default('Patient');
            $table->rememberToken();
            $table->timestamps();
            $table->string('profile_photo_path')->nullable();
            $table->string('locale', 5)->nullable();
        });

        $oldCols = Schema::getColumnListing($sourceTable);
        $newCols = Schema::getColumnListing('users_new_role_fix');
        $ordered = array_values(array_filter($oldCols, fn ($c) => in_array($c, $newCols, true)));
        $q = implode(', ', array_map(fn ($c) => '"'.$c.'"', $ordered));

        DB::statement("INSERT INTO users_new_role_fix ($q) SELECT $q FROM \"{$sourceTable}\"");

        Schema::drop($sourceTable);

        Schema::rename('users_new_role_fix', 'users');

        DB::statement('PRAGMA foreign_keys=ON');
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        //
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            // SQLite : enum() ajoute un CHECK trop strict ; string accepte aussi « Laboratory » (migration 2026_04_10).
            if (Schema::getConnection()->getDriverName() === 'sqlite') {
                $table->string('role')->default('Patient');
            } else {
                $table->enum('role', ['Admin', 'Doctor', 'Patient'])->default('Patient');
            }
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};

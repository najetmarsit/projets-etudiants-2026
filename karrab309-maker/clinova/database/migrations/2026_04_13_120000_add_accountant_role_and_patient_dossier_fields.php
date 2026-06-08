<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('Admin','Doctor','Patient','Laboratory','Accountant') NOT NULL DEFAULT 'Patient'");
        }

        Schema::table('patients', function (Blueprint $table) {
            $table->string('qr_public_token', 64)->nullable()->unique()->after('post_op_report');
            $table->timestamp('admission_at')->nullable()->after('qr_public_token');
            $table->timestamp('discharge_at')->nullable()->after('admission_at');
            $table->text('billing_notes')->nullable()->after('discharge_at');
        });
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('Admin','Doctor','Patient','Laboratory') NOT NULL DEFAULT 'Patient'");
        }

        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn(['qr_public_token', 'admission_at', 'discharge_at', 'billing_notes']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->decimal('billing_total_due', 12, 2)->nullable()->after('billing_notes');
            $table->json('billing_breakdown')->nullable()->after('billing_total_due');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->string('provider', 32)->nullable()->after('currency'); // manual, stripe, paypal
            $table->string('external_id', 191)->nullable()->index()->after('provider');
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn(['billing_total_due', 'billing_breakdown']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['provider', 'external_id']);
        });
    }
};

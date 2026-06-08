<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('health_indicators', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('recorded_at');
        });
    }

    public function down(): void
    {
        Schema::table('health_indicators', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });
    }
};

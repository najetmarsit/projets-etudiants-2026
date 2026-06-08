<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_billable_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id')->index();
            $table->string('kind', 50)->index(); // visit, medication, analysis, meal, ...
            $table->string('label', 255);
            $table->decimal('amount', 10, 2);
            $table->dateTime('performed_at')->nullable()->index();
            $table->unsignedBigInteger('created_by_user_id')->nullable()->index();

            // Optional linkage to a "source" record (health_indicator, nursing_note, report...)
            $table->string('source_type', 120)->nullable()->index();
            $table->unsignedBigInteger('source_id')->nullable()->index();

            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('created_by_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_billable_items');
    }
};


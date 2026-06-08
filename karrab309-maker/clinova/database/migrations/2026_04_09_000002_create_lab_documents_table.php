<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('original_filename');
            $table->string('stored_path');
            $table->string('mime_type', 127)->default('application/pdf');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_documents');
    }
};

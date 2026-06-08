<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('movement_date');
            $table->string('direction', 8); // in | out — entrée (achat/réception) ou sortie (consommation)
            $table->string('category', 64)->default('other'); // material, accessory, consumable, other
            $table->string('label');
            $table->decimal('quantity', 12, 3)->nullable();
            $table->string('unit', 32)->nullable();
            /** Valeur monétaire : achats (in) = sortie de trésorerie ; sorties (out) = valeur estimée consommée */
            $table->decimal('total_value', 14, 2)->default(0);
            $table->string('currency', 8)->default('TND');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['movement_date', 'direction']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};

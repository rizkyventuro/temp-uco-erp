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
        Schema::create('t_poo_transfer_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('t_poo_transfer_id')
                ->constrained('t_poo_transfers')
                ->onDelete('cascade');

            $table->foreignUuid('t_poo_collection_id')
                ->constrained('t_poo_collections')
                ->onDelete('restrict');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_poo_transfer_items');
    }
};

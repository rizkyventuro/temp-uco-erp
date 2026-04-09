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
        Schema::create('t_poo_ownership_chains', function (Blueprint $table) {
            $table->id();

            $table->foreignUuid('t_poo_collection_id')
                ->constrained('t_poo_collections')
                ->onDelete('restrict');

            $table->foreignId('from_user_id')
                ->nullable() // null = pengambilan pertama (origin)
                ->constrained('users')
                ->onDelete('restrict');

            $table->foreignId('to_user_id')
                ->constrained('users')
                ->onDelete('restrict');

            $table->string('transaction_code'); // referensi ke collection/transfer
            $table->tinyInteger('type'); // 1=collection, 2=transfer

            $table->timestamp('transferred_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_poo_ownership_chains');
    }
};

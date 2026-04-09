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
        Schema::create('t_poo_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('restrict');

            $table->foreignId('counterpart_id')  // pengirim/penerima (nullable, hanya untuk transfer)
                ->nullable()
                ->constrained('users')
                ->onDelete('restrict');

            $table->string('transaction_code');
            $table->decimal('volume', 10, 2);
            $table->tinyInteger('type'); // 1=collection, 2=transfer_out, 3=transfer_in

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_poo_histories');
    }
};

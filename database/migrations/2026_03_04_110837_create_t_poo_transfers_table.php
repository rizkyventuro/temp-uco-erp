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
        Schema::create('t_poo_transfers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sender_id')
                ->constrained('users')
                ->onDelete('restrict');

            $table->foreignId('receiver_id')
                ->nullable()          // diisi saat penerima klaim
                ->constrained('users')
                ->onDelete('restrict');

            $table->string('transfer_code')->unique();
            $table->decimal('volume_requested', 10, 2);  // volume yang diminta
            $table->decimal('volume_actual', 10, 2);     // volume aktual setelah FIFO
            $table->tinyInteger('status')->default(0);   // 0=pending, 1=claimed, 2=cancelled

            $table->timestamp('expires_at')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_poo_transfers');
    }
};

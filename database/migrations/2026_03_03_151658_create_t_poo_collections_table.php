<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_poo_collections', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('m_poo_id')
                ->constrained('m_poos')
                ->restrictOnDelete();

            $table->foreignId('created_by')
                ->constrained('users')
                ->onDelete('restrict');

            $table->foreignId('current_owner_id')
                ->constrained('users')
                ->onDelete('restrict');

            $table->string('transaction_code')->unique();
            $table->decimal('volume', 10, 2);
            $table->date('collected_at');
            $table->string('photo')->nullable();
            $table->string('signature');
            $table->text('notes')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_poo_collections');
    }
};

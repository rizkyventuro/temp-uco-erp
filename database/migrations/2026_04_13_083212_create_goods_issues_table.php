<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_issues', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('transaction_number', 30)->unique();

            $table->date('date');

            $table->foreignUuid('buyer_id')
                ->constrained('buyers')
                ->restrictOnDelete();

            $table->foreignId('warehouse_id')
                ->constrained('warehouses')
                ->restrictOnDelete();

            $table->decimal('volume', 12, 2)->default(0);            // kg
            $table->decimal('selling_price', 15, 2)->default(0);     // per kg
            $table->decimal('total_price', 15, 2)->default(0);       // volume * selling_price

            $table->enum('status', ['pending', 'shipped', 'delivered', 'cancelled'])
                ->default('pending');

            $table->text('notes')->nullable();

            // Audit trail
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('date');
            $table->index('status');
            $table->index('buyer_id');
            $table->index('warehouse_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_issues');
    }
};

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
        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('transaction_number', 30)->unique();

            $table->date('date');

            $table->foreignUuid('supplier_id')
                ->constrained('suppliers')
                ->restrictOnDelete();

            $table->foreignId('warehouse_id')
                ->constrained('warehouses')
                ->restrictOnDelete();

            $table->decimal('volume', 12, 2)->default(0);            // kg
            $table->decimal('purchase_price', 15, 2)->default(0);    // per kg
            $table->decimal('total_price', 15, 2)->default(0);       // volume * purchase_price

            $table->enum('status', ['lunas', 'belum_lunas'])->default('lunas');
            $table->date('due_date')->nullable();                     // hanya jika belum_lunas / tempo

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
            $table->index('supplier_id');
            $table->index('warehouse_id');
            $table->index('due_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_receipts');
    }
};

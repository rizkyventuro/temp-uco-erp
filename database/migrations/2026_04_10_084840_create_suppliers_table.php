<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('code')->unique();                                    // SUP-001
            $table->string('name');
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->decimal('monthly_capacity', 15, 2)->nullable();             // kg
            $table->decimal('default_purchase_price', 15, 2)->default(0);       // Rp/kg
            $table->string('bank')->nullable();
            $table->string('account_number')->nullable();
            $table->string('account_holder')->nullable();
            $table->string('npwp', 30)->nullable();
            $table->string('pic')->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();

            // Payment terms
            $table->enum('payment_term', ['cash', 'tempo'])->default('cash');
            $table->unsignedInteger('payment_term_days')->nullable();

            $table->boolean('is_active')->default(true);
            $table->string('inactive_reason')->nullable();
            $table->string('foto_path')->nullable();
            $table->string('foto_disk')->nullable();

            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};

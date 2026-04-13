<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 255);
            $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->enum('type', ['Utama', 'Cabang', 'Transit', 'Sementara'])->default('Utama');
            $table->text('address')->nullable();
            $table->string('pic', 255)->nullable();
            $table->string('pic_phone', 20)->nullable();
            $table->decimal('capacity_max', 15, 2)->default(0);
            $table->decimal('min_stock', 15, 2)->default(0);
            $table->decimal('price_estimate', 15, 2)->nullable()->comment('Estimated price per kg');
            $table->decimal('operating_cost', 15, 2)->default(0)->comment('Monthly cost (Rp)');
            $table->boolean('is_active')->default(true);
            $table->string('inactive_reason', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};

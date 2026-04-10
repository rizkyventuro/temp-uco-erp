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

            $table->string('kode')->unique();                           // SUP-001
            $table->string('nama');
            $table->string('telepon', 20)->nullable();
            $table->string('email')->nullable();
            $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->decimal('kapasitas_per_bulan', 15, 2)->nullable(); // kg
            $table->decimal('harga_beli_default', 15, 2)->default(0);  // Rp/kg
            $table->string('bank')->nullable();
            $table->string('no_rekening')->nullable();
            $table->string('atas_nama')->nullable();
            $table->string('npwp', 30)->nullable();
            $table->string('pic')->nullable();
            $table->text('alamat')->nullable();
            $table->text('catatan')->nullable();

            // Termin pembayaran
            $table->enum('termin', ['cash', 'tempo'])->default('cash');
            $table->unsignedInteger('termin_hari')->nullable();

            $table->boolean('is_active')->default(true);
            $table->string('alasan_nonaktif')->nullable();
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

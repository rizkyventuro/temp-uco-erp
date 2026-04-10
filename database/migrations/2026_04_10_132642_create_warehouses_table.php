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
            $table->string('kode', 20)->unique();
            $table->string('nama', 255);
            $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->enum('tipe', ['Utama', 'Cabang', 'Transit', 'Sementara'])->default('Utama');
            $table->text('alamat')->nullable();
            $table->string('pic', 255)->nullable();
            $table->string('telepon_pic', 20)->nullable();
            $table->decimal('kapasitas_maks', 15, 2)->default(0);
            $table->decimal('stok_minimum', 15, 2)->default(0);
            $table->decimal('harga_estimasi', 15, 2)->nullable()->comment('Estimasi harga per kg');
            $table->decimal('biaya_operasional', 15, 2)->default(0)->comment('Biaya per bulan (Rp)');
            $table->boolean('is_active')->default(true);
            $table->string('alasan_nonaktif', 255)->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};

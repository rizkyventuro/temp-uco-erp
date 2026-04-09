<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // Kontak
            $table->string('phone', 20)->nullable();

            // Data Diri
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->string('occupation')->nullable();

            // Alamat
            $table->text('address')->nullable();
            $table->string('province')->nullable();
            $table->string('city')->nullable();
            $table->string('district')->nullable();
            $table->string('village')->nullable();
            $table->string('postal_code', 10)->nullable();

            // Identitas
            $table->string('id_card_number', 20)->nullable();
            $table->string('id_card_photo_path')->nullable();
            $table->string('id_card_photo_disk')->nullable();

            // Catatan verifikasi dari admin
            $table->text('verification_note')->nullable();
            $table->foreignId('noted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('noted_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provinces', function (Blueprint $table) {
            $table->id();
            $table->string('referensi_id', 32)->unique();
            $table->string('nama', 255);
            $table->timestamps();
        });

        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('referensi_id', 32)->unique();
            $table->foreignId('province_id')->constrained('provinces')->cascadeOnDelete();
            $table->string('nama', 255);
            $table->timestamps();
        });

        // Pastikan user_profiles sudah ada sebelum alter
        if (Schema::hasTable('user_profiles')) {
            Schema::table('user_profiles', function (Blueprint $table) {
                if (Schema::hasColumn('user_profiles', 'province')) {
                    $table->dropColumn('province');
                }
                if (Schema::hasColumn('user_profiles', 'city')) {
                    $table->dropColumn('city');
                }

                $table->string('province_referensi_id', 32)->nullable()->after('address');
                $table->string('city_referensi_id', 32)->nullable()->after('province_referensi_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('user_profiles')) {
            Schema::table('user_profiles', function (Blueprint $table) {
                $table->dropColumn(['province_referensi_id', 'city_referensi_id']);

                $table->string('province')->nullable()->after('address');
                $table->string('city')->nullable()->after('province');
            });
        }

        Schema::dropIfExists('cities');
        Schema::dropIfExists('provinces');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permission_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');           // Label tampilan, e.g. "Manajemen User"
            $table->string('key')->unique();  // Slug unik, e.g. "management-user"
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        Schema::create('permission_group_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permission_group_id')->constrained('permission_groups')->cascadeOnDelete();
            $table->string('permission_name'); // Nama permission spatie, e.g. "view user"
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_group_items');
        Schema::dropIfExists('permission_groups');
    }
};

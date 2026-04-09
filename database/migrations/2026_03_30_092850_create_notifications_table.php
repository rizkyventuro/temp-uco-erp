<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->tinyInteger('type')->default(1); // 1=info | 2=success | 3=warning | 4=error
            $table->string('title');
            $table->text('message');
            $table->string('icon')->nullable();
            $table->string('url')->nullable();
            $table->uuid('notifiable_id');
            $table->string('notifiable_type');
            $table->index(['notifiable_id', 'notifiable_type']);
            $table->unsignedBigInteger('sender_id')->nullable(); 
            $table->foreign('sender_id')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
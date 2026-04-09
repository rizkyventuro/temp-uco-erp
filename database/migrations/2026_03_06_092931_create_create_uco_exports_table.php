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
        Schema::create('uco_exports', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('export_code')->unique();
            $table->date('export_date');
            $table->decimal('volume', 10, 2);

            // Foreign key ke t_poo_collections
            $table->foreignUuid('t_poo_collection_id')
                ->constrained('t_poo_collections')
                ->onDelete('restrict');

            $table->string('refinery_name');
            $table->tinyInteger('status')->default(1);
            $table->timestamp('locked_at')->nullable();
            $table->string('iscc_path')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uco_exports');
    }
};

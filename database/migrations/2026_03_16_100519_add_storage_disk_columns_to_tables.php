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
        Schema::table('t_poo_collections', function (Blueprint $table) {
            $table->string('photo_disk')->nullable()->after('photo');
            $table->string('signature_disk')->nullable()->after('signature');
        });

        // The exact table name for UcoExport might be uco_exports or t_uco_exports
        // We will check and update the correct one based on schema
        if (Schema::hasTable('uco_exports')) {
            Schema::table('uco_exports', function (Blueprint $table) {
                $table->string('iscc_disk')->nullable()->after('iscc_path');
            });
        } elseif (Schema::hasTable('t_uco_exports')) {
            Schema::table('t_uco_exports', function (Blueprint $table) {
                $table->string('iscc_disk')->nullable()->after('iscc_path');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('t_poo_collections', function (Blueprint $table) {
            $table->dropColumn(['photo_disk', 'signature_disk']);
        });

        if (Schema::hasTable('uco_exports')) {
            Schema::table('uco_exports', function (Blueprint $table) {
                $table->dropColumn('iscc_disk');
            });
        } elseif (Schema::hasTable('t_uco_exports')) {
            Schema::table('t_uco_exports', function (Blueprint $table) {
                $table->dropColumn('iscc_disk');
            });
        }
    }
};

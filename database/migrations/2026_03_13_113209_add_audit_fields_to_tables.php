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
        $tables = [
            'users',
            't_poo_transfers',
            't_poo_transfer_items',
            't_poo_histories',
            't_poo_ownership_chains',
            'uco_exports',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $tableBlueprint) {
                $tableBlueprint->foreignId('created_by')->nullable()->constrained('users')->onDelete('restrict');
                $tableBlueprint->foreignId('updated_by')->nullable()->constrained('users')->onDelete('restrict');
                $tableBlueprint->foreignId('deleted_by')->nullable()->constrained('users')->onDelete('restrict');
            });
        }

        // Tables that already have created_by
        $tablesWithCreatedBy = [
            'm_poos',
            't_poo_collections',
        ];

        foreach ($tablesWithCreatedBy as $table) {
            Schema::table($table, function (Blueprint $tableBlueprint) {
                $tableBlueprint->foreignId('updated_by')->nullable()->constrained('users')->onDelete('restrict');
                $tableBlueprint->foreignId('deleted_by')->nullable()->constrained('users')->onDelete('restrict');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'users',
            't_poo_transfers',
            't_poo_transfer_items',
            't_poo_histories',
            't_poo_ownership_chains',
            'uco_exports',
            'm_poos',
            't_poo_collections',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $tableBlueprint) {
                $tableBlueprint->dropForeign(['updated_by']);
                $tableBlueprint->dropColumn('updated_by');
                $tableBlueprint->dropForeign(['deleted_by']);
                $tableBlueprint->dropColumn('deleted_by');
                
                // Only drop created_by for tables where we added it
                if (!in_array($tableBlueprint->getTable(), ['m_poos', 't_poo_collections'])) {
                    $tableBlueprint->dropForeign(['created_by']);
                    $tableBlueprint->dropColumn('created_by');
                }
            });
        }
    }
};

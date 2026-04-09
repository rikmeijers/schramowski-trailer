<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            if (!Schema::hasColumn('reservations', 'partial_paid_amount')) {
                $table->decimal('partial_paid_amount', 10, 2)->nullable()->after('payment_status');
            }
        });

        // Add index only when it doesn't exist yet (some DBs may already have an index name
        // due to earlier/manual schema changes).
        if (Schema::hasColumn('reservations', 'partial_paid_amount')) {
            $indexName = 'reservations_partial_paid_amount_index';

            $dbName = DB::connection()->getDatabaseName();
            $exists = DB::table('information_schema.statistics')
                ->where('table_schema', $dbName)
                ->where('table_name', 'reservations')
                ->where('index_name', $indexName)
                ->exists();

            if (!$exists) {
                Schema::table('reservations', function (Blueprint $table) {
                    $table->index(['partial_paid_amount']);
                });
            }
        }
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            try { $table->dropIndex(['partial_paid_amount']); } catch (\Throwable $e) {}

            if (Schema::hasColumn('reservations', 'partial_paid_amount')) {
                $table->dropColumn('partial_paid_amount');
            }
        });
    }
};

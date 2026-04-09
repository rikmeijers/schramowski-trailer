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
        Schema::table('reservations', function (Blueprint $table) {
            // Nieuwe date-based velden (tijd/slot wordt uitgefaseerd)
            if (!Schema::hasColumn('reservations', 'start_date')) {
                $table->date('start_date')->nullable()->after('customer_email');
            }
            if (!Schema::hasColumn('reservations', 'end_date')) {
                $table->date('end_date')->nullable()->after('start_date');
            }

            // Zakelijke velden
            if (!Schema::hasColumn('reservations', 'customer_number')) {
                $table->string('customer_number')->nullable()->after('customer_email');
            }
            if (!Schema::hasColumn('reservations', 'company_name')) {
                $table->string('company_name')->nullable()->after('customer_number');
            }

            // Status & betaling
            if (!Schema::hasColumn('reservations', 'status')) {
                $table->string('status')->default('confirmed')->after('company_name'); // pending|confirmed
            }
            if (!Schema::hasColumn('reservations', 'payment_status')) {
                $table->string('payment_status')->default('unpaid')->after('status'); // unpaid|paid
            }

            // Extra service pakketten
            if (!Schema::hasColumn('reservations', 'service_selber_beladen')) {
                $table->boolean('service_selber_beladen')->default(false)->after('notes');
            }
            if (!Schema::hasColumn('reservations', 'service_lehr')) {
                $table->boolean('service_lehr')->default(false)->after('service_selber_beladen');
            }
            if (!Schema::hasColumn('reservations', 'service_paket')) {
                $table->boolean('service_paket')->default(false)->after('service_lehr');
            }
        });

        // Indexes: alleen toevoegen als de benodigde kolommen bestaan.
        // (We laten duplicates-errors vermijden door te checken of de kolommen bestaan;
        //  index-exists checks zijn DB-driver afhankelijk.)
        if (Schema::hasColumns('reservations', ['trailer_id', 'start_date', 'end_date'])) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->index(['trailer_id', 'start_date', 'end_date']);
            });
        }
        if (Schema::hasColumn('reservations', 'status')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->index(['status']);
            });
        }
        if (Schema::hasColumn('reservations', 'payment_status')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->index(['payment_status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            // Index namen verschillen per DB; we attempt best-effort drops.
            try { $table->dropIndex(['trailer_id', 'start_date', 'end_date']); } catch (\Throwable $e) {}
            try { $table->dropIndex(['status']); } catch (\Throwable $e) {}
            try { $table->dropIndex(['payment_status']); } catch (\Throwable $e) {}

            $columns = [
                'start_date',
                'end_date',
                'customer_number',
                'company_name',
                'status',
                'payment_status',
                'service_selber_beladen',
                'service_lehr',
                'service_paket',
            ];

            $toDrop = [];
            foreach ($columns as $col) {
                if (Schema::hasColumn('reservations', $col)) {
                    $toDrop[] = $col;
                }
            }

            if ($toDrop) {
                $table->dropColumn($toDrop);
            }
        });
    }
};

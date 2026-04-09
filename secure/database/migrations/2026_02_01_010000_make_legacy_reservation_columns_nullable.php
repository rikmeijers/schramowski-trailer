<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            // The app now uses date-based start_date/end_date.
            // Legacy columns must be nullable to allow creating new reservations.
            if (Schema::hasColumn('reservations', 'starts_at')) {
                $table->dateTime('starts_at')->nullable()->change();
            }
            if (Schema::hasColumn('reservations', 'ends_at')) {
                $table->dateTime('ends_at')->nullable()->change();
            }
            if (Schema::hasColumn('reservations', 'slot')) {
                $table->string('slot')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        // Best-effort: revert to NOT NULL (original state) for legacy compatibility.
        Schema::table('reservations', function (Blueprint $table) {
            if (Schema::hasColumn('reservations', 'starts_at')) {
                $table->dateTime('starts_at')->nullable(false)->change();
            }
            if (Schema::hasColumn('reservations', 'ends_at')) {
                $table->dateTime('ends_at')->nullable(false)->change();
            }
            if (Schema::hasColumn('reservations', 'slot')) {
                $table->string('slot')->nullable(false)->change();
            }
        });
    }
};

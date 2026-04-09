<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('trailer_id')->constrained('trailers')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // medewerker die reserveert

            $table->string('customer_first_name');
            $table->string('customer_last_name');
            $table->string('customer_phone');
            $table->string('customer_email');

            $table->dateTime('starts_at');
            $table->dateTime('ends_at');

            $table->string('slot'); // TWO_DAYS | TWO_WEEKS

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['trailer_id', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};


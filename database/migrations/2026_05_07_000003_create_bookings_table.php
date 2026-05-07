<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('court_id')->constrained()->onDelete('cascade');
            $table->foreignId('club_id')->constrained()->onDelete('cascade');
            $table->date('booking_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('duration_minutes');
            $table->decimal('total_price', 10, 2);
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed', 'no_show'])->default('pending');
            $table->text('notes')->nullable();
            $table->integer('players_count')->default(2);
            $table->timestamps();
            $table->softDeletes();

            // Evitar reservas duplicadas en el mismo horario y cancha
            $table->unique(['court_id', 'booking_date', 'start_time', 'end_time', 'deleted_at'], 'unique_booking_per_slot');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};

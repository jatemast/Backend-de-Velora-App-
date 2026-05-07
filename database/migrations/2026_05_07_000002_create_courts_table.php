<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('surface_type', ['cement', 'clay', 'grass', 'carpet', 'acrylic', 'other'])->default('cement');
            $table->enum('court_type', ['indoor', 'outdoor'])->default('outdoor');
            $table->boolean('is_covered')->default(false);
            $table->boolean('has_lighting')->default(false);
            $table->integer('max_players')->default(4);
            $table->decimal('price_per_hour', 10, 2);
            $table->decimal('price_per_session', 10, 2)->nullable();
            $table->json('photos')->nullable();
            $table->json('amenities')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courts');
    }
};

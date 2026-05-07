<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->unique();
            $table->string('phone')->nullable();
            $table->string('avatar_url')->nullable();
            $table->text('bio')->nullable();
            $table->enum('gender', ['male', 'female', 'other', 'prefer_not_to_say'])->nullable();
            $table->date('birth_date')->nullable();
            $table->string('phone_alternative')->nullable();
            $table->integer('skill_level')->default(1);
            $table->enum('preferred_hand', ['left', 'right', 'ambidextrous'])->nullable();
            $table->json('preferences')->nullable();
            $table->json('availability')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};

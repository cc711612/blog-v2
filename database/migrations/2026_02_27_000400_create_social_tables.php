<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('socials', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->unsignedTinyInteger('social_type');
            $table->string('social_type_value');
            $table->string('image')->nullable();
            $table->boolean('followed')->default(false);
            $table->string('token')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['email', 'social_type']);
            $table->unique(['social_type', 'social_type_value']);
        });

        Schema::create('user_social', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('social_id')->constrained('socials')->cascadeOnUpdate()->cascadeOnDelete();

            $table->primary(['user_id', 'social_id']);
            $table->unique('social_id');
            $table->index(['social_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_social');
        Schema::dropIfExists('socials');
    }
};

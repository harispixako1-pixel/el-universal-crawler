<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();

            $table->string('title');

            $table->string('url')
                ->unique();

            $table->dateTime('published_at')
                ->nullable();

            $table->string('author')
                ->nullable();

            $table->longText('content')
                ->nullable();

            $table->string('category')
                ->nullable();

            $table->string('source')
                ->default('El Universal');

            $table->timestamps();

            $table->index('published_at');
            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
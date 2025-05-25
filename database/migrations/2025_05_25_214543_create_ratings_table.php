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
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->onDelete('cascade');
            
            // reviewer: the person writing the review
            $table->foreignId('reviewer_id')
                ->constrained('users')
                ->onDelete('cascade');
            
            // reviewed user: the owner of the book or the person being reviewed
            $table->foreignId('reviewed_user_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->unsignedTinyInteger('rating'); // example: 1–5
            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};

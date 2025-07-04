<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('book_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['buy', 'rent'])->default('buy');
            $table->integer('quantity')->default(1);
            $table->timestamps();

            $table->unique(['user_id', 'book_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};

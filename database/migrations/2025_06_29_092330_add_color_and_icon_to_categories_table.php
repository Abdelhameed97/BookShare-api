<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('color_class')->default('blue');
            $table->string('icon')->default('Book');
        });
    }

    public function down()
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['color_class', 'icon']);
        });
    }
};
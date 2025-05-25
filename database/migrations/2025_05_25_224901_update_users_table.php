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
  
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone_number')->unique()->after('password');
            $table->string('national_id')->unique()->after('phone_number');
            $table->string('id_image')->nullable()->after('national_id');
            $table->enum('role', ['admin', 'owner', 'client'])->default('client')->after('id_image');
            $table->string('location')->nullable()->after('role');
        });
    }

     /**
     * Reverse the migrations.
     */
    
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone_number', 'national_id', 'id_image', 'role', 'location']);
        });
    }


   
};

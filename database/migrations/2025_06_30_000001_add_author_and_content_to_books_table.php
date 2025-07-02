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
        Schema::table('books', function (Blueprint $table) {
            if (!Schema::hasColumn('books', 'author')) {
                $table->string('author')->nullable()->after('status');
            }
            if (!Schema::hasColumn('books', 'content')) {
                $table->text('content')->nullable()->after('author');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            if (Schema::hasColumn('books', 'author')) {
                $table->dropColumn('author');
            }
            if (Schema::hasColumn('books', 'content')) {
                $table->dropColumn('content');
            }
        });
    }
};

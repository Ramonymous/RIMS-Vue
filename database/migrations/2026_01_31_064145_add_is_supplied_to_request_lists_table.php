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
        Schema::table('request_lists', function (Blueprint $table) {
            $table->boolean('is_supplied')->default(false)->after('is_urgent');
            $table->index('is_supplied');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('request_lists', function (Blueprint $table) {
            $table->dropIndex(['is_supplied']);
            $table->dropColumn('is_supplied');
        });
    }
};

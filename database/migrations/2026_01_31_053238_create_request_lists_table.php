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
        Schema::create('request_lists', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('request_id');
            $table->uuid('part_id');

            $table->foreign('request_id')
                ->references('id')->on('requests')
                ->cascadeOnDelete();

            $table->foreign('part_id')
                ->references('id')->on('parts')
                ->restrictOnDelete();

            $table->integer('qty');
            $table->boolean('is_urgent')->default(false);
            $table->timestamps();

            $table->unique(['request_id', 'part_id']);
            $table->index('request_id');
            $table->index('part_id');
            $table->index(['request_id', 'part_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_lists');
    }
};

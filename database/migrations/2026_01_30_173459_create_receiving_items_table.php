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
        Schema::create('receiving_items', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('receiving_id');
            $table->uuid('part_id');

            $table->foreign('receiving_id')
                ->references('id')->on('receivings')
                ->cascadeOnDelete();

            $table->foreign('part_id')
                ->references('id')->on('parts')
                ->restrictOnDelete();

            $table->integer('qty');

            $table->timestamps();

            $table->unique(['receiving_id', 'part_id']);
            $table->index('receiving_id');
            $table->index('part_id');
            $table->index(['receiving_id', 'part_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receiving_items');
    }
};

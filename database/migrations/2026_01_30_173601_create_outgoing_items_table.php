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
        Schema::create('outgoing_items', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('outgoing_id');
            $table->uuid('part_id');

            $table->foreign('outgoing_id')
                ->references('id')->on('outgoings')
                ->cascadeOnDelete();

            $table->foreign('part_id')
                ->references('id')->on('parts')
                ->restrictOnDelete();

            $table->integer('qty');

            $table->timestamps();

            $table->unique(['outgoing_id', 'part_id']);
            $table->index('outgoing_id');
            $table->index('part_id');
            $table->index(['outgoing_id', 'part_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outgoing_items');
    }
};

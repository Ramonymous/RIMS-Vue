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
        Schema::create('part_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('part_id');
            $table->foreign('part_id')
                ->references('id')->on('parts')
                ->restrictOnDelete();

            $table->integer('stock_before');
            $table->enum('type', ['in', 'out']);
            $table->integer('qty');
            $table->integer('stock_after');

            $table->string('reference_type');
            $table->uuid('reference_id');

            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index('part_id');
            $table->index('type');
            $table->index('reference_type');
            $table->index('reference_id');
            $table->index(['reference_type', 'reference_id']);
            $table->index(['part_id', 'created_at']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('part_movements');
    }
};

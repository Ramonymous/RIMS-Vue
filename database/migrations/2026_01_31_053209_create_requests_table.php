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
        Schema::create('requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('request_number')->unique();

            $table->uuid('requested_by');
            $table->timestamp('requested_at');

            $table->string('destination')->nullable();
            $table->enum('status', ['draft', 'completed', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('request_number');
            $table->index('status');
            $table->index('requested_at');
            $table->index('created_at');

            $table->foreign('requested_by')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};

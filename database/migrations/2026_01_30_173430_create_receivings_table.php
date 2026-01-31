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
        Schema::create('receivings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('doc_number')->unique();

            $table->uuid('received_by');
            $table->timestamp('received_at');

            $table->enum('status', ['draft', 'completed', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->boolean('is_gr')->default(false);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('doc_number');
            $table->index('status');
            $table->index('is_gr');
            $table->index(['status', 'is_gr']);
            $table->index('received_at');
            $table->index('created_at');

            $table->foreign('received_by')
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
        Schema::dropIfExists('receivings');
    }
};

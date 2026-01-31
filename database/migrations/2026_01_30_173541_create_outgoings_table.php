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
        Schema::create('outgoings', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('doc_number')->unique();

            $table->uuid('issued_by');
            $table->foreign('issued_by')->references('id')->on('users');

            $table->timestamp('issued_at')->nullable();

            $table->enum('status', ['draft', 'completed', 'cancelled'])
                ->default('draft');

            $table->text('notes')->nullable();
            $table->boolean('is_gi')->default(false);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('doc_number');
            $table->index('status');
            $table->index('is_gi');
            $table->index(['status', 'is_gi']);
            $table->index('issued_at');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outgoings');
    }
};

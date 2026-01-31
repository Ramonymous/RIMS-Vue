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
        Schema::create('parts', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('part_number')->unique();
            $table->string('part_name');

            $table->string('customer_code')->nullable();
            $table->string('supplier_code')->nullable();

            $table->string('model')->nullable();
            $table->string('variant')->nullable();

            $table->unsignedInteger('standard_packing')->default(1);
            $table->integer('stock')->default(0);

            $table->string('address')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('part_number');
            $table->index('is_active');
            $table->index('stock');
            $table->index(['is_active', 'stock']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parts');
    }
};

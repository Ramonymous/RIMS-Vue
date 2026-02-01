<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->text('two_factor_secret')->nullable();
                $table->text('two_factor_recovery_codes')->nullable();
                $table->timestamp('two_factor_confirmed_at')->nullable();
                $table->json('permissions')->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('users', function (Blueprint $table) {
                if (! Schema::hasColumn('users', 'two_factor_secret')) {
                    $table->text('two_factor_secret')->nullable();
                }

                if (! Schema::hasColumn('users', 'two_factor_recovery_codes')) {
                    $table->text('two_factor_recovery_codes')->nullable();
                }

                if (! Schema::hasColumn('users', 'two_factor_confirmed_at')) {
                    $table->timestamp('two_factor_confirmed_at')->nullable();
                }
            });
        }

        if (! Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        if (! Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignUuid('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }

        if (! Schema::hasTable('cache')) {
            Schema::create('cache', function (Blueprint $table) {
                $table->string('key')->primary();
                $table->mediumText('value');
                $table->integer('expiration')->index();
            });
        }

        if (! Schema::hasTable('cache_locks')) {
            Schema::create('cache_locks', function (Blueprint $table) {
                $table->string('key')->primary();
                $table->string('owner');
                $table->integer('expiration')->index();
            });
        }

        if (! Schema::hasTable('jobs')) {
            Schema::create('jobs', function (Blueprint $table) {
                $table->id();
                $table->string('queue')->index();
                $table->longText('payload');
                $table->unsignedTinyInteger('attempts');
                $table->unsignedInteger('reserved_at')->nullable();
                $table->unsignedInteger('available_at');
                $table->unsignedInteger('created_at');
            });
        }

        if (! Schema::hasTable('job_batches')) {
            Schema::create('job_batches', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->string('name');
                $table->integer('total_jobs');
                $table->integer('pending_jobs');
                $table->integer('failed_jobs');
                $table->longText('failed_job_ids');
                $table->mediumText('options')->nullable();
                $table->integer('cancelled_at')->nullable();
                $table->integer('created_at');
                $table->integer('finished_at')->nullable();
            });
        }

        if (! Schema::hasTable('failed_jobs')) {
            Schema::create('failed_jobs', function (Blueprint $table) {
                $table->id();
                $table->string('uuid')->unique();
                $table->text('connection');
                $table->text('queue');
                $table->longText('payload');
                $table->longText('exception');
                $table->timestamp('failed_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('parts')) {
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

                $table->index('part_number');
                $table->index('is_active');
                $table->index('stock');
                $table->index(['is_active', 'stock']);
                $table->index('created_at');
            });
        }

        if (! Schema::hasTable('receivings')) {
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

        if (! Schema::hasTable('receiving_items')) {
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

        if (! Schema::hasTable('outgoings')) {
            Schema::create('outgoings', function (Blueprint $table) {
                $table->uuid('id')->primary();

                $table->string('doc_number')->unique();

                $table->uuid('issued_by');
                $table->foreign('issued_by')->references('id')->on('users');

                $table->timestamp('issued_at')->nullable();

                $table->enum('status', ['draft', 'completed', 'cancelled'])->default('draft');

                $table->text('notes')->nullable();
                $table->boolean('is_gi')->default(false);

                $table->timestamps();
                $table->softDeletes();

                $table->index('doc_number');
                $table->index('status');
                $table->index('is_gi');
                $table->index(['status', 'is_gi']);
                $table->index('issued_at');
                $table->index('created_at');
            });
        }

        if (! Schema::hasTable('outgoing_items')) {
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

        if (! Schema::hasTable('part_movements')) {
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

                $table->index('part_id');
                $table->index('type');
                $table->index('reference_type');
                $table->index('reference_id');
                $table->index(['reference_type', 'reference_id']);
                $table->index(['part_id', 'created_at']);
                $table->index('created_at');
            });
        }

        if (! Schema::hasTable('requests')) {
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

        if (! Schema::hasTable('request_lists')) {
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
                $table->boolean('is_supplied')->default(false);
                $table->timestamps();

                $table->unique(['request_id', 'part_id']);
                $table->index('request_id');
                $table->index('part_id');
                $table->index(['request_id', 'part_id']);
                $table->index('is_supplied');
            });
        } else {
            Schema::table('request_lists', function (Blueprint $table) {
                if (! Schema::hasColumn('request_lists', 'is_supplied')) {
                    $table->boolean('is_supplied')->default(false);
                    $table->index('is_supplied');
                }
            });
        }

        if (! Schema::hasTable('personal_access_tokens')) {
            Schema::create('personal_access_tokens', function (Blueprint $table) {
                $table->id();
                $table->string('tokenable_type');
                $table->string('tokenable_id', 36);
                $table->index(['tokenable_type', 'tokenable_id']);
                $table->text('name');
                $table->string('token', 64)->unique();
                $table->text('abilities')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable()->index();
                $table->timestamps();
            });
        } else {
            if (Schema::hasColumn('personal_access_tokens', 'tokenable_id')) {
                $driver = Schema::getConnection()->getDriverName();

                if ($driver === 'mysql') {
                    DB::statement("ALTER TABLE personal_access_tokens MODIFY tokenable_id VARCHAR(36)");
                } elseif ($driver === 'pgsql') {
                    DB::statement("ALTER TABLE personal_access_tokens ALTER COLUMN tokenable_id TYPE varchar(36)");
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('request_lists');
        Schema::dropIfExists('requests');
        Schema::dropIfExists('part_movements');
        Schema::dropIfExists('outgoing_items');
        Schema::dropIfExists('outgoings');
        Schema::dropIfExists('receiving_items');
        Schema::dropIfExists('receivings');
        Schema::dropIfExists('parts');
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};

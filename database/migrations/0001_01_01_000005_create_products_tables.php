<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('type'); // webhosting|vps|minecraft|fivem|domain|license|custom
            $table->string('module')->default('hosting'); // integration module used for provisioning
            $table->decimal('price_monthly', 12, 2)->default(0);
            $table->decimal('price_quarterly', 12, 2)->nullable();
            $table->decimal('price_semi_annually', 12, 2)->nullable();
            $table->decimal('price_annually', 12, 2)->nullable();
            $table->decimal('setup_fee', 12, 2)->default(0);
            $table->json('features')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_recurring')->default(true);
            $table->integer('stock')->nullable();
            $table->integer('sort_order')->default(0);
            // Index only; FK is added once server_groups exists (see 0006).
            $table->foreignId('server_group_id')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('price_monthly', 12, 2)->default(0);
            $table->decimal('price_quarterly', 12, 2)->nullable();
            $table->decimal('price_semi_annually', 12, 2)->nullable();
            $table->decimal('price_annually', 12, 2)->nullable();
            $table->decimal('setup_fee', 12, 2)->default(0);

            // Resource specs (used by provisioning drivers)
            $table->integer('disk_mb')->nullable();
            $table->integer('bandwidth_gb')->nullable();
            $table->integer('cpu_cores')->nullable();
            $table->integer('ram_mb')->nullable();
            $table->integer('swap_mb')->nullable();
            $table->integer('databases')->nullable();
            $table->integer('email_accounts')->nullable();
            $table->integer('domains')->nullable();
            $table->json('metadata')->nullable();

            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
        Schema::dropIfExists('products');
    }
};

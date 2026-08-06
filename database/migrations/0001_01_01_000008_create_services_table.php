<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('server_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('server_group_id')->nullable()->constrained('server_groups')->nullOnDelete();

            $table->string('name');
            $table->string('domain')->nullable();
            $table->string('status')->default('pending'); // pending|active|suspended|expired|cancelled|terminated
            $table->string('billing_cycle')->default('monthly');
            $table->decimal('price', 12, 2)->default(0);

            $table->string('username')->nullable();
            $table->text('password')->nullable(); // encrypted
            $table->string('remote_id')->nullable()->index(); // id on the provider side

            $table->json('provisioning_data')->nullable(); // details returned by the integration
            $table->json('metadata')->nullable();

            $table->timestamp('activated_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->timestamp('terminated_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};

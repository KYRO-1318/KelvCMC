<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('server_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('integration'); // plesk|pterodactyl|proxmox|manual
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('servers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_group_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('hostname');
            $table->string('ip_address')->nullable();
            $table->string('integration'); // plesk|pterodactyl|proxmox|manual
            $table->string('remote_id')->nullable()->index(); // id on the provider side
            $table->text('credentials')->nullable(); // encrypted
            $table->string('status')->default('online'); // online|offline|maintenance
            $table->string('location')->nullable();
            $table->decimal('load', 5, 2)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servers');
        Schema::dropIfExists('server_groups');
    }
};

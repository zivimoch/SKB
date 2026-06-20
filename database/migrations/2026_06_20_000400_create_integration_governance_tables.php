<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institutions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('code', 100)->unique();
            $table->string('name');
            $table->string('type', 100)->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('integration_clients', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('institution_id')->nullable()->constrained('institutions')->nullOnDelete();
            $table->string('key_id', 100)->unique();
            $table->string('name');
            $table->string('source_system', 64)->unique();
            $table->longText('scopes');
            $table->longText('secret_encrypted')->nullable();
            $table->longText('previous_secret_encrypted')->nullable();
            $table->string('environment', 30)->default('sandbox');
            $table->boolean('active')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });

        Schema::create('external_actors', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('integration_client_id')->constrained('integration_clients')->cascadeOnDelete();
            $table->string('external_id', 128);
            $table->string('name')->nullable();
            $table->string('role', 100)->nullable();
            $table->string('institution_name')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at');
            $table->timestamps();
            $table->unique(['integration_client_id', 'external_id']);
        });

        Schema::create('identity_providers', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('institution_id')->nullable()->constrained('institutions')->nullOnDelete();
            $table->string('name');
            $table->string('issuer')->unique();
            $table->string('discovery_url')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('identity_providers');
        Schema::dropIfExists('external_actors');
        Schema::dropIfExists('integration_clients');
        Schema::dropIfExists('institutions');
    }
};

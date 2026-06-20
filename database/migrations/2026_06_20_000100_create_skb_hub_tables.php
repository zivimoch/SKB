<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hub_cases', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('source_system', 32);
            $table->string('source_id', 128);
            $table->string('source_version', 100)->nullable();
            $table->string('registration_number')->nullable();
            $table->string('client_number')->nullable();
            $table->string('status', 50)->nullable();
            $table->date('reported_at')->nullable();
            $table->date('occurred_at')->nullable();
            $table->boolean('occurred_at_estimated')->default(false);
            $table->longText('summary_encrypted')->nullable();
            $table->longText('location_encrypted')->nullable();
            // JSON disimpan sebagai teks agar kompatibel dengan MySQL/MariaDB
            // lama. Service layer tetap melakukan encode/decode secara eksplisit.
            $table->longText('classifications')->nullable();
            $table->unsignedInteger('active_intervention_cycle')->default(1);
            $table->char('payload_hash', 64);
            $table->timestamp('source_updated_at')->nullable();
            $table->timestamp('last_synced_at');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['source_system', 'source_id']);
            $table->index(['source_system', 'source_updated_at']);
        });

        Schema::create('case_people', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('case_id')->constrained('hub_cases')->cascadeOnDelete();
            $table->string('source_id', 191)->nullable();
            $table->string('role', 30);
            $table->longText('identity_encrypted');
            $table->char('identity_hash', 64);
            $table->timestamps();
            $table->index(['case_id', 'role']);
        });

        Schema::create('case_event_histories', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('case_id')->constrained('hub_cases')->cascadeOnDelete();
            $table->string('source_id', 191)->nullable();
            $table->date('event_date')->nullable();
            $table->time('event_time')->nullable();
            $table->longText('description_encrypted');
            $table->timestamps();
        });

        Schema::create('assessments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('case_id')->constrained('hub_cases')->cascadeOnDelete();
            $table->string('source_id', 191)->nullable();
            $table->longText('content_encrypted');
            $table->timestamp('assessed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('intervention_cycles', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('case_id')->constrained('hub_cases')->cascadeOnDelete();
            $table->unsignedInteger('cycle_number');
            $table->string('status', 30)->default('active');
            $table->timestamps();
            $table->unique(['case_id', 'cycle_number']);
        });

        Schema::create('intervention_activities', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('cycle_id')->constrained('intervention_cycles')->cascadeOnDelete();
            $table->string('source_id', 128);
            $table->longText('title_encrypted');
            $table->date('scheduled_date')->nullable();
            $table->time('scheduled_time')->nullable();
            $table->timestamps();
            $table->unique(['cycle_id', 'source_id']);
        });

        Schema::create('intervention_reports', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('activity_id')->constrained('intervention_activities')->cascadeOnDelete();
            $table->string('source_id', 128);
            $table->string('officer_source_id', 191)->nullable();
            $table->string('status', 30);
            $table->longText('content_encrypted');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['activity_id', 'source_id']);
        });

        Schema::create('monitoring_evaluations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('cycle_id')->constrained('intervention_cycles')->cascadeOnDelete();
            $table->string('source_id', 191)->nullable();
            $table->longText('content_encrypted');
            $table->string('decision', 50)->nullable();
            $table->timestamps();
        });

        Schema::create('terminations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('case_id')->constrained('hub_cases')->cascadeOnDelete();
            $table->string('source_id', 191)->nullable();
            $table->string('type', 30);
            $table->string('status', 30)->nullable();
            $table->longText('reason_encrypted')->nullable();
            $table->timestamps();
        });

        Schema::create('integration_receipts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('client_key_id', 64);
            $table->string('idempotency_key', 120);
            $table->char('request_hash', 64);
            $table->unsignedSmallInteger('response_status');
            $table->longText('response_body');
            $table->timestamps();
            $table->unique(['client_key_id', 'idempotency_key']);
        });

        Schema::create('security_audit_logs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('actor_type', 50);
            $table->string('actor_id', 191);
            $table->string('action', 100);
            $table->string('resource_type', 64);
            $table->string('resource_id', 127)->nullable();
            $table->string('request_id', 128)->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->char('request_hash', 64)->nullable();
            $table->longText('metadata')->nullable();
            $table->timestamp('occurred_at');
            $table->index(['resource_type', 'resource_id']);
            $table->index('occurred_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_audit_logs');
        Schema::dropIfExists('integration_receipts');
        Schema::dropIfExists('terminations');
        Schema::dropIfExists('monitoring_evaluations');
        Schema::dropIfExists('intervention_reports');
        Schema::dropIfExists('intervention_activities');
        Schema::dropIfExists('intervention_cycles');
        Schema::dropIfExists('assessments');
        Schema::dropIfExists('case_event_histories');
        Schema::dropIfExists('case_people');
        Schema::dropIfExists('hub_cases');
    }
};

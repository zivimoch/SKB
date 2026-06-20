<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->uuid('external_id')->nullable()->unique()->after('id');
            $table->string('external_system', 64)->nullable()->after('external_id');
            $table->string('role', 100)->nullable()->after('email');
            $table->boolean('active')->default(true)->after('role');
        });

        Schema::table('intervention_activities', function (Blueprint $table): void {
            $table->string('origin_system', 64)->default('mokav2')->after('source_id');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::create('integration_officers', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('source_system', 64);
            $table->uuid('source_id');
            $table->string('name');
            $table->string('role', 100)->nullable();
            $table->string('institution')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['source_system', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_officers');
        Schema::table('intervention_activities', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn('origin_system');
        });
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['external_id']);
            $table->dropColumn(['external_id', 'external_system', 'role', 'active']);
        });
    }
};

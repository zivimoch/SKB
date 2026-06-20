<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hub_cases', function (Blueprint $table): void {
            $table->timestamp('profile_synced_at')->nullable()->after('last_synced_at');
        });
    }

    public function down(): void
    {
        Schema::table('hub_cases', function (Blueprint $table): void {
            $table->dropColumn('profile_synced_at');
        });
    }
};

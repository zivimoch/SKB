<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('integration_officers', function (Blueprint $table): void {
            $table->string('email')->nullable()->after('name');
        });

        Schema::create('case_integration_officers', function (Blueprint $table): void {
            $table->foreignUuid('case_id')->constrained('hub_cases')->cascadeOnDelete();
            $table->foreignUuid('officer_id')->constrained('integration_officers')->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['case_id', 'officer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_integration_officers');
        Schema::table('integration_officers', function (Blueprint $table): void {
            $table->dropColumn('email');
        });
    }
};

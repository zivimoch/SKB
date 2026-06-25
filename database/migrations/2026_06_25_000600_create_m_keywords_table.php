<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('m_keywords', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('source_system', 32);
            $table->string('source_id', 191)->nullable();
            $table->string('jabatan', 191)->nullable();
            $table->string('keyword', 191);
            $table->string('jenis_agenda', 50)->default('Layanan');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['source_system', 'source_id']);
            $table->index(['jenis_agenda', 'jabatan']);
            $table->index('keyword');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('m_keywords');
    }
};

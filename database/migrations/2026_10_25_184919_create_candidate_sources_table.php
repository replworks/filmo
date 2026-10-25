<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_candidate_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('filmography_candidate_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('kind');
            $table->string('url', 2048);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_sources');
    }
};

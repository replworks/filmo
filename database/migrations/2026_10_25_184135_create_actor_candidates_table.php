<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actor_candidates', function (Blueprint $table) {
            $table->id();
            $table->string('activity_name')->nullable();
            $table->string('name')->nullable();
            $table->string('related_link', 2048)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actor_candidates');
    }
};

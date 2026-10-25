<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('filmography_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_candidate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('youtube_video_id')->constrained()->cascadeOnDelete();
            $table->string('review_status')->default('pending')->index();
            $table->string('activity_name')->nullable();
            $table->string('name')->nullable();
            $table->string('related_link', 2048)->nullable();
            $table->string('video_title')->nullable();
            $table->string('channel_name')->nullable();
            $table->timestamp('published_date')->nullable();
            $table->string('youtube_video_url', 2048)->nullable();
            $table->string('timecode')->nullable();
            $table->string('collection_method')->nullable();
            $table->decimal('confidence', 4, 3)->nullable();
            $table->string('model_identifier')->nullable();
            $table->string('prompt_identifier')->nullable();
            $table->string('candidate_source_url', 2048)->nullable();
            $table->string('reviewer')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('filmography_candidates');
    }
};

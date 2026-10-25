<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('actor_candidates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('actor_identity_id');
        });

        Schema::rename('actor_candidates', 'actors');

        Schema::table('actors', function (Blueprint $table) {
            $table->renameColumn('related_link', 'url');
            $table->json('profile')->nullable();
            $table->unsignedBigInteger('merged_into_actor_id')->nullable()->index();
        });

        Schema::create('actor_video', function (Blueprint $table) {
            $table->foreignId('actor_id')->constrained('actors')->cascadeOnDelete();
            $table->foreignId('youtube_video_id')->constrained('youtube_videos')->cascadeOnDelete();
            $table->primary(['actor_id', 'youtube_video_id']);
        });

        DB::table('filmography_candidates')
            ->select(['actor_candidate_id', 'youtube_video_id'])
            ->orderBy('id')
            ->chunkById(500, function ($candidates): void {
                foreach ($candidates as $candidate) {
                    DB::table('actor_video')->insertOrIgnore([
                        'actor_id' => $candidate->actor_candidate_id,
                        'youtube_video_id' => $candidate->youtube_video_id,
                    ]);
                }
            });

        Schema::dropIfExists('filmography_candidates');
        Schema::dropIfExists('candidate_sources');
        Schema::dropIfExists('actor_identities');
    }

    public function down(): void
    {
        // This simplification is intentionally irreversible.
    }
};

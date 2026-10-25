<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actor_reference_urls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->constrained('actors')->cascadeOnDelete();
            $table->string('url', 2048);
            $table->timestamps();

            $table->unique(['actor_id', 'url']);
        });

        DB::table('actors')
            ->whereNotNull('url')
            ->orderBy('id')
            ->chunkById(500, function ($actors): void {
                foreach ($actors as $actor) {
                    DB::table('actor_reference_urls')->insertOrIgnore([
                        'actor_id' => $actor->id,
                        'url' => $actor->url,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });

        DB::table('actors')
            ->whereNotNull('profile')
            ->orderBy('id')
            ->chunkById(500, function ($actors): void {
                foreach ($actors as $actor) {
                    $profile = json_decode((string) $actor->profile, true);

                    if (! is_array($profile) || ! isset($profile['related_link']) || ! is_string($profile['related_link'])) {
                        continue;
                    }

                    DB::table('actor_reference_urls')->insertOrIgnore([
                        'actor_id' => $actor->id,
                        'url' => $profile['related_link'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    unset($profile['related_link']);

                    DB::table('actors')->where('id', $actor->id)->update([
                        'profile' => $profile === [] ? null : json_encode($profile, JSON_UNESCAPED_UNICODE),
                    ]);
                }
            });

        Schema::table('actors', function (Blueprint $table) {
            $table->dropColumn('url');
        });
    }

    public function down(): void
    {
        Schema::table('actors', function (Blueprint $table) {
            $table->string('url', 2048)->nullable();
        });

        DB::table('actor_reference_urls')
            ->select(['actor_id', 'url'])
            ->orderBy('id')
            ->chunkById(500, function ($urls): void {
                foreach ($urls as $url) {
                    DB::table('actors')
                        ->where('id', $url->actor_id)
                        ->whereNull('url')
                        ->update(['url' => $url->url]);
                }
            });

        Schema::dropIfExists('actor_reference_urls');
    }
};

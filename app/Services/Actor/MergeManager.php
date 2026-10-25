<?php

namespace App\Services\Actor;

use App\Models\Actor;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MergeManager
{
    /**
     * Merge the selected Actors into one surviving Actor.
     *
     * @param  array<int, int|string>  $actorIds
     *
     * @throws RuntimeException
     */
    public function merge(array $actorIds, int $survivorId, bool $confirmed = false): Actor
    {
        if (! $confirmed) {
            throw new RuntimeException('Confirm the Actor merge before saving it.');
        }

        $actorIds = array_values(array_unique(array_map('intval', $actorIds)));

        if (count($actorIds) < 2 || ! in_array($survivorId, $actorIds, true)) {
            throw new RuntimeException('Select at least two Actors and one surviving Actor.');
        }

        return DB::transaction(function () use ($actorIds, $survivorId): Actor {
            $actors = Actor::query()
                ->active()
                ->whereIn('id', $actorIds)
                ->get()
                ->keyBy('id');

            if ($actors->count() < 2 || ! $actors->has($survivorId)) {
                throw new RuntimeException('Select at least two unmerged Actors.');
            }

            $survivor = $actors->get($survivorId);
            $profile = $survivor->profile ?? [];

            foreach ($actors as $actor) {
                if ($actor->is($survivor)) {
                    continue;
                }

                foreach ($actor->videos()->pluck('youtube_videos.id') as $videoId) {
                    DB::table('actor_video')->insertOrIgnore([
                        'actor_id' => $survivor->id,
                        'youtube_video_id' => $videoId,
                    ]);
                }

                DB::table('actor_video')->where('actor_id', $actor->id)->delete();

                foreach ($actor->referenceUrls()->get() as $referenceUrl) {
                    $survivor->referenceUrls()->firstOrCreate(['url' => $referenceUrl->url]);
                    $referenceUrl->delete();
                }

                if ($survivor->activity_name === null && $actor->activity_name !== null) {
                    $survivor->activity_name = $actor->activity_name;
                }

                foreach (Actor::PROFILE_COLUMNS as $column) {
                    if ($survivor->{$column} === null && $actor->{$column} !== null) {
                        $survivor->{$column} = $actor->{$column};
                    }
                }

                foreach ($actor->profile ?? [] as $key => $value) {
                    if (! array_key_exists($key, $profile) && $value !== null) {
                        $profile[$key] = $value;
                    }
                }

                $actor->merged_into_actor_id = $survivor->id;
                $actor->save();

                Actor::where('merged_into_actor_id', $actor->id)
                    ->update(['merged_into_actor_id' => $survivor->id]);
            }

            $survivor->profile = $profile === [] ? null : $profile;
            $survivor->save();

            return $survivor;
        });
    }
}

<?php

namespace App\Services\Contribution;

use App\Jobs\CollectChannelVideos;
use App\Models\Actor;
use App\Models\Contribution;
use App\Models\User;
use App\Models\Video;
use Cable8mm\Youtube\Models\YoutubeChannel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ContributionManager
{
    public function pendingFor(User $user, Model $record): ?Contribution
    {
        return Contribution::query()
            ->where('user_id', $user->id)
            ->where('contributable_type', $record::class)
            ->where('contributable_id', $record->getKey())
            ->where('status', Contribution::STATUS_PENDING)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $payload
     *
     * @throws RuntimeException
     */
    public function submit(User $user, Model $record, array $payload): Contribution
    {
        return DB::transaction(function () use ($user, $record, $payload): Contribution {
            if ($this->pendingFor($user, $record) !== null) {
                throw new RuntimeException('이미 검토 대기 중인 기여가 있습니다. 기존 기여를 먼저 관리해 주세요.');
            }

            return Contribution::create([
                'user_id' => $user->id,
                'contributable_type' => $record::class,
                'contributable_id' => $record->getKey(),
                'payload' => $payload,
                'status' => Contribution::STATUS_PENDING,
            ]);
        });
    }

    /**
     * @throws RuntimeException
     */
    public function approve(Contribution $contribution, User $reviewer, ?string $note = null): void
    {
        if ($contribution->status !== Contribution::STATUS_PENDING) {
            throw new RuntimeException('Only pending contributions can be approved.');
        }

        DB::transaction(function () use ($contribution, $reviewer, $note): void {
            $this->apply($contribution);

            $contribution->update([
                'status' => Contribution::STATUS_APPROVED,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'review_note' => $note,
            ]);
        });
    }

    /**
     * @throws RuntimeException
     */
    public function reject(Contribution $contribution, User $reviewer, ?string $note = null): void
    {
        if ($contribution->status !== Contribution::STATUS_PENDING) {
            throw new RuntimeException('Only pending contributions can be rejected.');
        }

        $contribution->update([
            'status' => Contribution::STATUS_REJECTED,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_note' => $note,
        ]);
    }

    private function apply(Contribution $contribution): void
    {
        $record = $contribution->contributable;
        $payload = $contribution->payload;

        if ($record instanceof YoutubeChannel) {
            $record->update(Arr::only($payload, [
                'title',
                'handle',
                'uploads_playlist_id',
                'is_active',
            ]));

            $record->forceFill(['is_public' => true])->save();

            CollectChannelVideos::dispatch($record->id);

            return;
        }

        if ($record instanceof Video) {
            $record->update(Arr::only($payload, [
                'title',
                'description',
                'published_at',
                'duration',
                'thumbnail_url',
                'view_count',
                'like_count',
                'comment_count',
                'unavailable_at',
            ]));

            $record->forceFill(['is_public' => true])->save();

            if ($record->channel !== null && ! $record->channel->is_public) {
                $record->channel->forceFill(['is_public' => true])->save();
            }

            return;
        }

        if ($record instanceof Actor) {
            $this->applyActor($record, $payload);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function applyActor(Actor $actor, array $payload): void
    {
        if (isset($payload['association'])) {
            $videoId = (int) ($payload['video_id'] ?? 0);

            if ($payload['association'] === 'attach') {
                $actor->videos()->syncWithoutDetaching([$videoId]);
            } else {
                $actor->videos()->detach($videoId);
            }

            return;
        }

        $actor->update(Arr::only($payload, [
            'name',
            'activity_name',
            'birth_date',
            'birth_place',
            'education',
            'debut',
            'agency',
            'nationality',
            'profile',
        ]));

        $urls = collect($payload['reference_urls'] ?? [])->filter()->unique()->values();

        $actor->referenceUrls()->whereNotIn('url', $urls)->delete();

        foreach ($urls as $url) {
            $actor->referenceUrls()->firstOrCreate(['url' => $url]);
        }

        foreach (collect($payload['videos'] ?? [])->map(fn ($id): int => (int) $id) as $videoId) {
            $video = Video::find($videoId);

            if ($video === null) {
                continue;
            }

            $actor->videos()->syncWithoutDetaching([$videoId]);
            $video->forceFill(['is_public' => true])->save();
        }

        $actor->forceFill(['is_public' => true])->save();
    }
}

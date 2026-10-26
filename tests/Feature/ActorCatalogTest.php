<?php

namespace Tests\Feature;

use App\Filament\Resources\Actors\Pages\CreateActor;
use App\Filament\Resources\Actors\Pages\EditActor;
use App\Filament\Resources\Actors\Pages\ListActors;
use App\Filament\Resources\Actors\RelationManagers\ActorVideosRelationManager;
use App\Filament\Resources\YoutubeChannels\YoutubeChannelResource;
use App\Models\Actor;
use App\Models\User;
use Cable8mm\Youtube\Models\YoutubeChannel;
use Cable8mm\Youtube\Models\YoutubeVideo;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;
use Tests\TestCase;

class ActorCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Bus::fake();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_the_actor_list_shows_only_active_actors(): void
    {
        $active = Actor::factory()->create(['name' => 'Active Actor']);
        $merged = Actor::factory()->create(['name' => 'Merged Actor']);

        $active->update(['merged_into_actor_id' => null]);
        $merged->update(['merged_into_actor_id' => $active->id]);

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ListActors::class)
            ->assertActionExists('create')
            ->assertActionExists('channels')
            ->assertActionHasUrl('channels', YoutubeChannelResource::getUrl())
            ->assertCanSeeTableRecords([$active])
            ->assertCanNotSeeTableRecords([$merged]);
    }

    public function test_the_actor_list_has_an_empty_state(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->get('/admin/actors')
            ->assertOk()
            ->assertSee('등록된 배우가 없습니다');
    }

    public function test_the_operator_can_edit_actor_fields_and_profile_values(): void
    {
        $actor = Actor::factory()->create([
            'name' => 'Old Name',
            'activity_name' => null,
            'profile' => null,
        ]);

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(EditActor::class, ['record' => $actor->id])
            ->assertSeeLivewire(ActorVideosRelationManager::class)
            ->fillForm([
                'name' => 'New Name',
                'activity_name' => 'New Activity',
                'birth_date' => '1981-11-22',
                'birth_place' => 'Seoul',
                'education' => 'Example University',
                'debut' => '1996',
                'agency' => 'Example Agency',
                'nationality' => 'Korean',
                'referenceUrls' => [
                    ['url' => 'https://namu.wiki/w/actor'],
                    ['url' => 'https://search.naver.com/actor'],
                ],
                'profile' => ['instagram' => 'https://instagram.com/actor'],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $actor->refresh();

        $this->assertSame('New Name', $actor->name);
        $this->assertSame('New Activity', $actor->activity_name);
        $this->assertSame(
            ['https://namu.wiki/w/actor', 'https://search.naver.com/actor'],
            $actor->referenceUrls()->orderBy('id')->pluck('url')->all(),
        );
        $this->assertSame('1981-11-22', $actor->birth_date->toDateString());
        $this->assertSame('Seoul', $actor->birth_place);
        $this->assertSame('Example University', $actor->education);
        $this->assertSame('1996', $actor->debut);
        $this->assertSame('Example Agency', $actor->agency);
        $this->assertSame('Korean', $actor->nationality);
        $this->assertSame('https://instagram.com/actor', $actor->profile['instagram']);
    }

    public function test_the_operator_can_create_and_delete_an_actor(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(CreateActor::class)
            ->fillForm([
                'name' => 'Created Actor',
                'activity_name' => 'Created Activity',
                'birth_date' => '1990-01-01',
                'education' => 'Created University',
                'referenceUrls' => [
                    ['url' => 'https://namu.wiki/w/created'],
                ],
                'profile' => ['instagram' => 'https://instagram.com/created'],
            ])
            ->call('create')
            ->assertHasNoErrors();

        $actor = Actor::where('name', 'Created Actor')->firstOrFail();

        $this->assertSame('Created Activity', $actor->activity_name);
        $this->assertSame(
            ['https://namu.wiki/w/created'],
            $actor->referenceUrls()->pluck('url')->all(),
        );
        $this->assertSame('1990-01-01', $actor->birth_date->toDateString());
        $this->assertSame('Created University', $actor->education);
        $this->assertSame('https://instagram.com/created', $actor->profile['instagram']);

        $video = $this->video('created-actor-video');
        $actor->videos()->attach($video->id);

        Livewire::test(ListActors::class)
            ->callTableAction('delete', $actor);

        $this->assertDatabaseMissing('actors', ['id' => $actor->id]);
        $this->assertDatabaseMissing('actor_video', [
            'actor_id' => $actor->id,
            'youtube_video_id' => $video->id,
        ]);
    }

    public function test_the_actor_page_has_an_open_url_action_when_a_reference_url_is_saved(): void
    {
        $actor = Actor::factory()->create();
        $actor->referenceUrls()->create(['url' => 'https://namu.wiki/w/actor']);

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(EditActor::class, ['record' => $actor->id])
            ->assertActionVisible('openUrl')
            ->assertActionHasUrl('openUrl', 'https://namu.wiki/w/actor');
    }

    public function test_the_operator_can_add_and_remove_an_actor_video(): void
    {
        $actor = Actor::factory()->create();
        $firstVideo = $this->video('video-1');
        $secondVideo = $this->video('video-2');

        $actor->videos()->attach($firstVideo->id);

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ActorVideosRelationManager::class, [
            'ownerRecord' => $actor,
            'pageClass' => EditActor::class,
        ])
            ->assertCanSeeTableRecords([$firstVideo])
            ->assertTableActionExists('addVideo')
            ->callTableAction('addVideo', null, ['video_id' => $secondVideo->id])
            ->callTableAction('removeVideo', $firstVideo);

        $this->assertSame([$secondVideo->id], $actor->videos()->pluck('youtube_videos.id')->all());
    }

    public function test_the_operator_can_merge_selected_actors_from_the_list(): void
    {
        $survivor = Actor::factory()->create(['name' => 'Survivor']);
        $other = Actor::factory()->create(['name' => 'Other']);
        $video = $this->video('video-1');

        $survivor->videos()->attach($video->id);
        $other->videos()->attach($video->id);

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ListActors::class)
            ->callTableBulkAction('merge', [$survivor, $other], ['survivor_id' => $survivor->id])
            ->assertNotified('배우를 합쳤습니다');

        $this->assertSame($survivor->id, $other->refresh()->merged_into_actor_id);
        $this->assertSame(1, $survivor->videos()->count());
    }

    public function test_matching_names_do_not_merge_actors_without_an_explicit_merge(): void
    {
        $first = Actor::factory()->create(['name' => 'Same Name']);
        $second = Actor::factory()->create(['name' => 'Same Name']);

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ListActors::class)
            ->assertCanSeeTableRecords([$first, $second]);

        $this->assertNull($first->merged_into_actor_id);
        $this->assertNull($second->merged_into_actor_id);
    }

    public function test_actor_optional_fields_can_be_saved_empty(): void
    {
        $actor = Actor::factory()->create([
            'name' => 'Old Name',
            'activity_name' => 'Old Activity',
            'birth_date' => '1980-01-01',
            'birth_place' => 'Busan',
            'education' => 'Old School',
            'debut' => '1990',
            'agency' => 'Old Agency',
            'nationality' => 'Korean',
            'profile' => ['instagram' => 'https://instagram.com/old'],
        ]);
        $actor->referenceUrls()->create(['url' => 'https://old.example']);

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(EditActor::class, ['record' => $actor->id])
            ->assertSeeLivewire(ActorVideosRelationManager::class)
            ->fillForm([
                'name' => 'Only Name',
                'activity_name' => null,
                'birth_date' => null,
                'birth_place' => null,
                'education' => null,
                'debut' => null,
                'agency' => null,
                'nationality' => null,
                'referenceUrls' => [],
                'profile' => null,
            ])
            ->call('save')
            ->assertHasNoErrors();

        $actor->refresh();

        $this->assertSame('Only Name', $actor->name);
        $this->assertNull($actor->activity_name);
        $this->assertNull($actor->birth_date);
        $this->assertNull($actor->birth_place);
        $this->assertNull($actor->education);
        $this->assertNull($actor->debut);
        $this->assertNull($actor->agency);
        $this->assertNull($actor->nationality);
        $this->assertSame(0, $actor->referenceUrls()->count());
        $this->assertSame([], $actor->profile);
    }

    public function test_a_failed_actor_save_is_shown_and_keeps_entered_values(): void
    {
        $actor = Actor::factory()->create(['name' => 'Original Name']);

        Actor::updating(function (): void {
            throw new \RuntimeException('The database rejected the save.');
        });

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(EditActor::class, ['record' => $actor->id])
            ->assertSeeLivewire(ActorVideosRelationManager::class)
            ->fillForm(['name' => 'Entered Name'])
            ->call('save')
            ->assertNotified('배우 저장 실패')
            ->assertSet('data.name', 'Entered Name');

        $this->assertSame('Original Name', $actor->refresh()->name);
    }

    public function test_the_actor_video_list_is_limited_to_fifty_and_newest_first(): void
    {
        $actor = Actor::factory()->create();
        $channel = $this->channel();
        $newest = Carbon::parse('2026-10-25T00:00:00Z');
        $videos = [];

        for ($index = 0; $index < 51; $index++) {
            $videos[] = $this->video('video-'.$index, $channel, $newest->copy()->subMinutes($index));
        }

        $actor->videos()->attach(collect($videos)->pluck('id'));

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ActorVideosRelationManager::class, [
            'ownerRecord' => $actor,
            'pageClass' => EditActor::class,
        ])
            ->assertCountTableRecords(51)
            ->assertCanSeeTableRecords([$videos[0], $videos[49]], inOrder: true)
            ->assertCanNotSeeTableRecords([$videos[50]]);
    }

    public function test_the_actor_pages_require_authentication(): void
    {
        $actor = Actor::factory()->create();

        $this->get('/admin/actors')->assertRedirect('/admin/login');
        $this->get('/admin/actors/'.$actor->id.'/edit')->assertRedirect('/admin/login');
    }

    private function channel(): YoutubeChannel
    {
        return YoutubeChannel::withoutEvents(fn (): YoutubeChannel => YoutubeChannel::create([
            'channel_id' => 'UC'.substr(md5(uniqid()), 0, 22),
            'title' => 'Test Channel',
            'uploads_playlist_id' => 'UU'.substr(md5(uniqid()), 0, 22),
        ]));
    }

    private function video(string $videoId, ?YoutubeChannel $channel = null, ?Carbon $publishedAt = null): YoutubeVideo
    {
        $channel ??= $this->channel();

        return YoutubeVideo::create([
            'youtube_channel_id' => $channel->id,
            'video_id' => $videoId,
            'title' => 'Video '.$videoId,
            'description' => 'Description',
            'published_at' => $publishedAt ?? Carbon::parse('2026-10-01T00:00:00Z'),
            'duration' => 'PT10M',
        ]);
    }
}

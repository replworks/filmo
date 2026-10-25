<?php

namespace Tests\Feature;

use App\Filament\Pages\ChannelImport;
use App\Models\User;
use Cable8mm\Youtube\Models\YoutubeChannel;
use Cable8mm\Youtube\Models\YoutubeVideo;
use Cable8mm\Youtube\Sync\ChannelInputResolver;
use Cable8mm\Youtube\Youtube;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Mockery\MockInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\Support\FailingYoutubeChannelsImport;
use Tests\Support\TestableChannelImport;
use Tests\TestCase;

class ChannelImportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var array<int, string>
     */
    private array $files = [];

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            File::delete($file);
        }

        parent::tearDown();
    }

    public function test_the_import_page_requires_authentication(): void
    {
        $this->get('/admin/channel-import')->assertRedirect('/admin/login');
    }

    public function test_the_import_page_is_rendered_for_an_operator(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/admin/channel-import')
            ->assertOk()
            ->assertSee('Channel Import')
            ->assertSee('YouTube Channel Spreadsheet');
    }

    public function test_the_import_action_processes_an_uploaded_spreadsheet(): void
    {
        $this->mockYoutubeHandle();

        Livewire::test(ChannelImport::class)
            ->set('data.spreadsheet', UploadedFile::fake()->createWithContent('channels.csv', "channel\n@YouTube\n"))
            ->call('import')
            ->assertHasNoErrors()
            ->assertSet('importError', null);

        $this->assertDatabaseHas('youtube_channels', ['channel_id' => 'UCBR8-60-B28hp2BmDPdntcQ']);
    }

    public function test_it_registers_channels_and_reports_a_result_for_every_processed_row(): void
    {
        $this->mockYoutube();

        $page = new TestableChannelImport;
        $page->importForTest($this->csv(<<<'CSV'
        channel
        UCBR8-60-B28hp2BmDPdntcQ
        @YouTube
        https://www.youtube.com/channel/UCBR8-60-B28hp2BmDPdntcQ
        @thisusernamedoesnotexist12345
        not a channel
        CSV), 'csv');

        $this->assertNull($page->importError);
        $this->assertSame([
            ['row' => 2, 'input' => 'UCBR8-60-B28hp2BmDPdntcQ', 'result' => 'created'],
            ['row' => 3, 'input' => '@YouTube', 'result' => 'updated'],
            ['row' => 4, 'input' => 'https://www.youtube.com/channel/UCBR8-60-B28hp2BmDPdntcQ', 'result' => 'updated'],
            ['row' => 5, 'input' => '@thisusernamedoesnotexist12345', 'result' => 'failed: channel not found'],
            ['row' => 6, 'input' => 'not a channel', 'result' => 'failed: unsupported input'],
        ], $page->results);

        $this->assertDatabaseCount('youtube_channels', 1);
        $this->assertDatabaseHas('youtube_channels', [
            'channel_id' => 'UCBR8-60-B28hp2BmDPdntcQ',
            'handle' => '@youtube',
            'title' => 'YouTube',
            'uploads_playlist_id' => 'UUBR8-60-B28hp2BmDPdntcQ',
        ]);
    }

    public function test_importing_an_existing_channel_preserves_its_videos_and_sync_state(): void
    {
        $channel = YoutubeChannel::create([
            'channel_id' => 'UCBR8-60-B28hp2BmDPdntcQ',
            'handle' => '@youtube',
            'title' => 'Old title',
            'uploads_playlist_id' => 'UUBR8-60-B28hp2BmDPdntcQ',
            'sync_status' => 'ok',
            'last_synced_at' => $syncedAt = now()->subDay()->startOfSecond(),
        ]);

        $video = YoutubeVideo::create([
            'youtube_channel_id' => $channel->id,
            'video_id' => 'video-1',
            'title' => 'A collected Video',
            'published_at' => now()->subWeek(),
        ]);

        $this->mockYoutubeHandle();

        $page = new TestableChannelImport;
        $page->importForTest($this->csv("channel\n@YouTube\n"), 'csv');

        $this->assertSame([['row' => 2, 'input' => '@YouTube', 'result' => 'updated']], $page->results);

        $channel->refresh();

        $this->assertSame('YouTube', $channel->title);
        $this->assertSame('ok', $channel->sync_status);
        $this->assertTrue($channel->last_synced_at->equalTo($syncedAt));
        $this->assertDatabaseHas('youtube_videos', ['id' => $video->id, 'video_id' => 'video-1']);
    }

    public function test_it_imports_an_xlsx_spreadsheet(): void
    {
        $this->mockYoutubeHandle();

        $path = $this->path('xlsx');
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray([['channel'], ['@YouTube']]);
        (new Xlsx($spreadsheet))->save($path);

        $page = new TestableChannelImport;
        $page->importForTest($path, 'xlsx');

        $this->assertNull($page->importError);
        $this->assertSame([['row' => 2, 'input' => '@YouTube', 'result' => 'created']], $page->results);
        $this->assertDatabaseHas('youtube_channels', ['channel_id' => 'UCBR8-60-B28hp2BmDPdntcQ']);
    }

    public function test_it_imports_an_xls_spreadsheet(): void
    {
        $this->mockYoutubeHandle();

        $path = $this->path('xls');
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray([['channel'], ['@YouTube']]);
        (new Xls($spreadsheet))->save($path);

        $page = new TestableChannelImport;
        $page->importForTest($path, 'xls');

        $this->assertNull($page->importError);
        $this->assertSame([['row' => 2, 'input' => '@YouTube', 'result' => 'created']], $page->results);
        $this->assertDatabaseHas('youtube_channels', ['channel_id' => 'UCBR8-60-B28hp2BmDPdntcQ']);
    }

    public function test_it_reports_a_spreadsheet_without_a_channel_column(): void
    {
        $page = new TestableChannelImport;
        $page->importForTest($this->csv("name\nYouTube\n"), 'csv');

        $this->assertSame([], $page->results);
        $this->assertSame('The spreadsheet has no channel column.', $page->importError);
        $this->assertDatabaseCount('youtube_channels', 0);
    }

    public function test_it_keeps_processed_row_results_when_processing_fails(): void
    {
        $page = new TestableChannelImport;
        $page->importOverride = new FailingYoutubeChannelsImport;
        $page->importForTest($this->csv("channel\n@YouTube\n"), 'csv');

        $this->assertStringContainsString('The spreadsheet could not be processed', (string) $page->importError);
        $this->assertSame([
            ['row' => 2, 'input' => 'first processed row', 'result' => 'created'],
        ], $page->results);
    }

    public function test_it_rejects_an_unsupported_file_type(): void
    {
        $page = new TestableChannelImport;
        $page->importForTest($this->csv("channel\n@YouTube\n"), 'txt');

        $this->assertSame('Unsupported file type: use csv, xlsx, or xls.', $page->importError);
        $this->assertNull($page->results);
    }

    private function mockYoutubeHandle(): void
    {
        $channel = $this->channel();

        $this->mock(Youtube::class, function (MockInterface $mock) use ($channel): void {
            $mock->shouldReceive('getChannelByHandle')
                ->with('@YouTube', [], ChannelInputResolver::PART)
                ->once()
                ->andReturn($channel);
        });
    }

    private function mockYoutube(): void
    {
        $channel = $this->channel();

        $this->mock(Youtube::class, function (MockInterface $mock) use ($channel): void {
            $mock->shouldReceive('getChannelById')
                ->with('UCBR8-60-B28hp2BmDPdntcQ', [], ChannelInputResolver::PART)
                ->twice()
                ->andReturn($channel);

            $mock->shouldReceive('getChannelByHandle')
                ->with('@YouTube', [], ChannelInputResolver::PART)
                ->once()
                ->andReturn($channel);

            $mock->shouldReceive('getChannelByHandle')
                ->with('@thisusernamedoesnotexist12345', [], ChannelInputResolver::PART)
                ->once()
                ->andReturnNull();

            $mock->shouldNotReceive('getChannelByName');
        });
    }

    private function channel(): object
    {
        return (object) [
            'id' => 'UCBR8-60-B28hp2BmDPdntcQ',
            'snippet' => (object) [
                'title' => 'YouTube',
                'customUrl' => '@youtube',
            ],
            'contentDetails' => (object) [
                'relatedPlaylists' => (object) ['uploads' => 'UUBR8-60-B28hp2BmDPdntcQ'],
            ],
        ];
    }

    private function csv(string $contents): string
    {
        $path = $this->path('csv');
        File::put($path, $contents);

        return $path;
    }

    private function path(string $extension): string
    {
        $path = storage_path('app/testing/'.uniqid('channels-', true).'.'.$extension);
        File::ensureDirectoryExists(dirname($path));
        $this->files[] = $path;

        return $path;
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\TestableChannelImport;
use Tests\TestCase;

#[Group('live')]
class ChannelImportLiveTest extends TestCase
{
    use RefreshDatabase;

    private ?string $path = null;

    protected function setUp(): void
    {
        parent::setUp();

        Bus::fake();

        if (! env('RUN_LIVE_TESTS', false)) {
            $this->markTestSkipped('Set RUN_LIVE_TESTS=true to run live boundary tests.');
        }

        $key = (string) config('youtube.key');

        if ($key === '' || $key === 'YOUR_API_KEY') {
            $this->markTestSkipped('YOUTUBE_API_KEY is not configured.');
        }
    }

    protected function tearDown(): void
    {
        if ($this->path !== null) {
            File::delete($this->path);
        }

        parent::tearDown();
    }

    public function test_it_registers_a_live_channel_and_reports_a_resolution_failure(): void
    {
        $this->path = storage_path('app/testing/live-channels-'.uniqid().'.csv');
        File::ensureDirectoryExists(dirname($this->path));
        File::put($this->path, "channel\n@YouTube\n@thisusernamedoesnotexist12345\nnot a channel\n");

        $page = new TestableChannelImport;
        $page->importForTest($this->path, 'csv');

        $this->assertNull($page->importError);
        $this->assertSame([
            ['row' => 2, 'input' => '@YouTube', 'result' => 'created'],
            ['row' => 3, 'input' => '@thisusernamedoesnotexist12345', 'result' => 'failed: channel not found'],
            ['row' => 4, 'input' => 'not a channel', 'result' => 'failed: unsupported input'],
        ], $page->results);

        $this->assertDatabaseHas('youtube_channels', [
            'channel_id' => 'UCBR8-60-B28hp2BmDPdntcQ',
            'title' => 'YouTube',
        ]);
    }
}

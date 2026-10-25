<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class CollectionScheduleTest extends TestCase
{
    public function test_daily_collection_is_scheduled_at_0400_kst(): void
    {
        $this->artisan('schedule:list')->assertSuccessful();

        $event = collect(app(Schedule::class)->events())
            ->first(fn ($event): bool => str_contains((string) $event->command, 'filmo:collect-channels'));

        $this->assertNotNull($event);
        $this->assertSame('0 4 * * *', $event->expression);
        $this->assertSame('Asia/Seoul', $event->timezone);
    }
}

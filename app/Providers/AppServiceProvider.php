<?php

namespace App\Providers;

use App\Jobs\CollectChannelVideos;
use Cable8mm\Youtube\Models\YoutubeChannel;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        YoutubeChannel::created(function (YoutubeChannel $channel): void {
            if ($channel->is_public === false) {
                return;
            }

            CollectChannelVideos::dispatch($channel->id);
        });
    }
}

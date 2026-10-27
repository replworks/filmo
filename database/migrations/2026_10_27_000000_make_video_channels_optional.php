<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('youtube_videos', function (Blueprint $table): void {
            $table->unsignedBigInteger('youtube_channel_id')->nullable()->change();
            $table->string('channelid')->nullable()->index();
            $table->string('channel_title')->nullable();
        });

        DB::table('youtube_videos')
            ->join('youtube_channels', 'youtube_channels.id', '=', 'youtube_videos.youtube_channel_id')
            ->whereNull('youtube_videos.channelid')
            ->select([
                'youtube_videos.id',
                'youtube_channels.channel_id as channelid',
                'youtube_channels.title as channel_title',
            ])
            ->orderBy('youtube_videos.id')
            ->chunkById(500, function ($videos): void {
                foreach ($videos as $video) {
                    DB::table('youtube_videos')->where('id', $video->id)->update([
                        'channelid' => $video->channelid,
                        'channel_title' => $video->channel_title,
                    ]);
                }
            }, 'youtube_videos.id', 'id');
    }

    public function down(): void
    {
        Schema::table('youtube_videos', function (Blueprint $table): void {
            $table->dropColumn(['channelid', 'channel_title']);
            $table->unsignedBigInteger('youtube_channel_id')->nullable(false)->change();
        });
    }
};

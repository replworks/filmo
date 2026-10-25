<?php

namespace App\Filament\Resources\YoutubeVideos\Schemas;

use App\Filament\Resources\YoutubeChannels\YoutubeChannelResource;
use App\Filament\Resources\YoutubeVideos\YoutubeVideoResource;
use App\Models\Video;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class YoutubeVideoInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('title')
                    ->label('Title'),
                TextEntry::make('video_id')
                    ->label('Video ID'),
                TextEntry::make('channel.title')
                    ->label('Channel')
                    ->url(fn (Video $video): string => YoutubeChannelResource::getUrl('view', ['record' => $video->youtube_channel_id])),
                TextEntry::make('published_at')
                    ->label('Published at')
                    ->dateTime(),
                TextEntry::make('availability_state')
                    ->label('Availability')
                    ->state(fn (Video $video): string => YoutubeVideoResource::availabilityState($video))
                    ->badge()
                    ->color(fn (Video $video): string => YoutubeVideoResource::availabilityColor($video)),
                TextEntry::make('extraction_state')
                    ->label('Extraction state')
                    ->state(fn (Video $video): string => YoutubeVideoResource::extractionState($video))
                    ->badge()
                    ->color(fn (Video $video): string => YoutubeVideoResource::extractionColor($video)),
                TextEntry::make('extraction_error')
                    ->label('Extraction error')
                    ->placeholder('—'),
                TextEntry::make('extracted_at')
                    ->label('Extracted at')
                    ->dateTime()
                    ->placeholder('—'),
                TextEntry::make('duration')
                    ->label('Duration')
                    ->placeholder('—'),
                TextEntry::make('view_count')
                    ->label('View count')
                    ->numeric()
                    ->placeholder('—'),
                TextEntry::make('like_count')
                    ->label('Like count')
                    ->numeric()
                    ->placeholder('—'),
                TextEntry::make('comment_count')
                    ->label('Comment count')
                    ->numeric()
                    ->placeholder('—'),
                TextEntry::make('description')
                    ->label('Description')
                    ->placeholder('—')
                    ->columnSpanFull(),
                TextEntry::make('youtube_url')
                    ->label('YouTube URL')
                    ->state(fn (Video $video): string => YoutubeVideoResource::videoUrl($video))
                    ->url(fn (Video $video): string => YoutubeVideoResource::videoUrl($video), shouldOpenInNewTab: true)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }
}

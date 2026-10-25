<?php

namespace App\Filament\Resources\YoutubeChannels\RelationManagers;

use App\Filament\Resources\YoutubeChannels\YoutubeChannelResource;
use Cable8mm\Youtube\Models\YoutubeVideo;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VideosRelationManager extends RelationManager
{
    protected static string $relationship = 'videos';

    protected static ?string $title = 'Videos';

    protected static ?string $recordTitleAttribute = 'title';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('published_at')
                    ->label('Published')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('availability')
                    ->state(fn (YoutubeVideo $video): string => $video->unavailable_at === null ? 'Available' : 'Unavailable')
                    ->badge()
                    ->color(fn (YoutubeVideo $video): string => $video->unavailable_at === null ? 'success' : 'danger'),
                TextColumn::make('youtube_url')
                    ->label('YouTube URL')
                    ->state(fn (YoutubeVideo $video): string => YoutubeChannelResource::videoUrl($video))
                    ->url(fn (YoutubeVideo $video): string => YoutubeChannelResource::videoUrl($video), shouldOpenInNewTab: true)
                    ->limit(50),
            ])
            ->defaultSort('published_at', 'desc')
            ->paginated([50])
            ->defaultPaginationPageOption(50)
            ->emptyStateHeading('No Videos')
            ->emptyStateDescription('This Channel has no collected Videos yet.');
    }
}

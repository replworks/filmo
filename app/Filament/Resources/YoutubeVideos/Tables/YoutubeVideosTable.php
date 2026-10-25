<?php

namespace App\Filament\Resources\YoutubeVideos\Tables;

use App\Filament\Resources\YoutubeVideos\YoutubeVideoResource;
use App\Jobs\ExtractVideoActors;
use App\Models\Video;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class YoutubeVideosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('channel.title')
                    ->label('Channel')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('published_at')
                    ->label('Published at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('availability_state')
                    ->label('Availability')
                    ->state(fn (Video $video): string => YoutubeVideoResource::availabilityState($video))
                    ->badge()
                    ->color(fn (Video $video): string => YoutubeVideoResource::availabilityColor($video)),
                TextColumn::make('extraction_state')
                    ->label('Extraction')
                    ->state(fn (Video $video): string => YoutubeVideoResource::extractionState($video))
                    ->badge()
                    ->color(fn (Video $video): string => YoutubeVideoResource::extractionColor($video)),
                TextColumn::make('view_count')
                    ->label('Views')
                    ->numeric()
                    ->sortable()
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('youtube_channel_id')
                    ->label('Channel')
                    ->relationship('channel', 'title')
                    ->searchable(),
                SelectFilter::make('extraction_status')
                    ->label('Extraction')
                    ->options([
                        ExtractVideoActors::STATUS_IN_PROGRESS => 'In progress',
                        ExtractVideoActors::STATUS_COMPLETED => 'Completed',
                        ExtractVideoActors::STATUS_FAILED => 'Failed',
                    ]),
                SelectFilter::make('availability')
                    ->label('Availability')
                    ->options([
                        'available' => 'Available',
                        'unavailable' => 'Unavailable',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        'available' => $query->whereNull('unavailable_at'),
                        'unavailable' => $query->whereNotNull('unavailable_at'),
                        default => $query,
                    }),
            ])
            ->defaultSort('published_at', 'desc')
            ->recordUrl(fn (Video $video): string => YoutubeVideoResource::getUrl('view', ['record' => $video]))
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->modalDescription('이 영상과 배우-영상 연결이 함께 삭제됩니다.'),
            ])
            ->emptyStateHeading('No Videos')
            ->emptyStateDescription('Videos appear here after a registered Channel is collected.')
            ->emptyStateIcon('heroicon-o-film');
    }
}

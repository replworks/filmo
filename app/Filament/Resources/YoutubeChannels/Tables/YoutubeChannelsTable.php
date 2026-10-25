<?php

namespace App\Filament\Resources\YoutubeChannels\Tables;

use App\Filament\Resources\YoutubeChannels\YoutubeChannelResource;
use Cable8mm\Youtube\Models\YoutubeChannel;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class YoutubeChannelsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Channel name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('handle')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('collection_state')
                    ->label('Collection state')
                    ->state(fn (YoutubeChannel $channel): string => YoutubeChannelResource::collectionState($channel))
                    ->badge()
                    ->color(fn (YoutubeChannel $channel): string => YoutubeChannelResource::collectionColor($channel)),
                TextColumn::make('last_synced_at')
                    ->label('Last synchronized')
                    ->dateTime()
                    ->placeholder('Never')
                    ->sortable(),
                TextColumn::make('videos_count')
                    ->label('Videos')
                    ->counts('videos')
                    ->numeric()
                    ->sortable(),
            ])
            ->defaultSort('title')
            ->recordUrl(fn (YoutubeChannel $channel): string => YoutubeChannelResource::getUrl('view', ['record' => $channel]))
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->modalDescription('이 채널의 모든 영상과 배우-영상 연결이 함께 삭제됩니다.'),
            ])
            ->emptyStateHeading('No Channels')
            ->emptyStateDescription('Import a YouTube Channel Spreadsheet to register Channels.')
            ->emptyStateIcon('heroicon-o-video-camera');
    }
}

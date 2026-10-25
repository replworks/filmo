<?php

namespace App\Filament\Resources\YoutubeSyncErrors\Schemas;

use App\Filament\Resources\YoutubeChannels\YoutubeChannelResource;
use Cable8mm\Youtube\Models\YoutubeSyncError;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class YoutubeSyncErrorInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('channel.title')
                    ->label('Channel')
                    ->placeholder('—')
                    ->url(fn (YoutubeSyncError $error): ?string => $error->youtube_channel_id === null
                        ? null
                        : YoutubeChannelResource::getUrl('view', ['record' => $error->youtube_channel_id])),
                TextEntry::make('reason')
                    ->label('Reason'),
                TextEntry::make('http_code')
                    ->label('HTTP code')
                    ->numeric()
                    ->placeholder('—'),
                TextEntry::make('created_at')
                    ->label('Occurred at')
                    ->dateTime(),
                TextEntry::make('message')
                    ->label('Message')
                    ->columnSpanFull(),
                TextEntry::make('updated_at')
                    ->label('Updated at')
                    ->dateTime(),
            ])
            ->columns(2);
    }
}

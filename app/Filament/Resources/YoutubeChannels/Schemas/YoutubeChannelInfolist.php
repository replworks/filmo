<?php

namespace App\Filament\Resources\YoutubeChannels\Schemas;

use App\Filament\Resources\YoutubeChannels\YoutubeChannelResource;
use Cable8mm\Youtube\Models\YoutubeChannel;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class YoutubeChannelInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('title')
                    ->label('Channel name'),
                TextEntry::make('handle')
                    ->placeholder('—'),
                TextEntry::make('channel_id')
                    ->label('Channel ID'),
                TextEntry::make('collection_state')
                    ->label('Collection state')
                    ->state(fn (YoutubeChannel $channel): string => YoutubeChannelResource::collectionState($channel))
                    ->badge()
                    ->color(fn (YoutubeChannel $channel): string => YoutubeChannelResource::collectionColor($channel)),
                TextEntry::make('last_synced_at')
                    ->label('Last synchronized')
                    ->dateTime()
                    ->placeholder('Never'),
                TextEntry::make('videos_count')
                    ->label('Videos')
                    ->state(fn (YoutubeChannel $channel): int => $channel->videos()->count()),
                TextEntry::make('last_error_reason')
                    ->label('Last error')
                    ->placeholder('—'),
            ]);
    }
}

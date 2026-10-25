<?php

namespace App\Filament\Resources\YoutubeChannels\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class YoutubeChannelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Channel')
                    ->description('YouTube collection may overwrite edits to synchronization fields.')
                    ->schema([
                        TextInput::make('channel_id')
                            ->label('Channel ID')
                            ->required()
                            ->unique(ignoreRecord: true),
                        TextInput::make('handle')
                            ->label('Handle'),
                        TextInput::make('title')
                            ->label('Channel name')
                            ->required(),
                        TextInput::make('uploads_playlist_id')
                            ->label('Uploads playlist ID'),
                        Toggle::make('is_active')
                            ->label('Active'),
                        TextInput::make('sync_status')
                            ->label('Sync status'),
                        DateTimePicker::make('last_synced_at')
                            ->label('Last synchronized at'),
                        TextInput::make('last_error_reason')
                            ->label('Last error'),
                    ])->columns(2),
            ]);
    }
}

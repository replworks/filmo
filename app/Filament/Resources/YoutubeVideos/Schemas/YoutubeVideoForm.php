<?php

namespace App\Filament\Resources\YoutubeVideos\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class YoutubeVideoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Video')
                    ->description('YouTube collection may overwrite edits to these fields.')
                    ->schema([
                        Select::make('youtube_channel_id')
                            ->label('Channel')
                            ->relationship('channel', 'title')
                            ->searchable()
                            ->required(),
                        TextInput::make('video_id')
                            ->label('Video ID')
                            ->required()
                            ->unique(ignoreRecord: true),
                        TextInput::make('title')
                            ->label('Title')
                            ->required(),
                        Textarea::make('description')
                            ->label('Description')
                            ->rows(4),
                        DateTimePicker::make('published_at')
                            ->label('Published at')
                            ->required(),
                        TextInput::make('duration')
                            ->label('Duration'),
                        TextInput::make('thumbnail_url')
                            ->label('Thumbnail URL')
                            ->url(),
                        TextInput::make('view_count')
                            ->label('View count')
                            ->numeric(),
                        TextInput::make('like_count')
                            ->label('Like count')
                            ->numeric(),
                        TextInput::make('comment_count')
                            ->label('Comment count')
                            ->numeric(),
                        DateTimePicker::make('unavailable_at')
                            ->label('Unavailable at'),
                    ])->columns(2),
            ]);
    }
}

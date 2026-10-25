<?php

namespace App\Filament\Resources\FilmographyCandidates\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FilmographyCandidateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Actor Candidate')->schema([
                    TextInput::make('activity_name')
                        ->label('Activity Name'),
                    TextInput::make('name')
                        ->label('Name'),
                    TextInput::make('related_link')
                        ->label('Related Link')
                        ->url(),
                ]),
                Section::make('Filmography')->schema([
                    TextInput::make('video_title')
                        ->label('Video title'),
                    TextInput::make('channel_name')
                        ->label('Channel name'),
                    DateTimePicker::make('published_date')
                        ->label('Published date'),
                    TextInput::make('youtube_video_url')
                        ->label('YouTube video URL')
                        ->url(),
                    TextInput::make('timecode'),
                    TextInput::make('collection_method')
                        ->label('Collection method'),
                    TextInput::make('confidence')
                        ->numeric()
                        ->step(0.001),
                    TextInput::make('model_identifier')
                        ->label('Model identifier'),
                    TextInput::make('prompt_identifier')
                        ->label('Prompt identifier'),
                    TextInput::make('candidate_source_url')
                        ->label('Candidate Source URL')
                        ->url(),
                ]),
            ]);
    }
}

<?php

namespace App\Filament\Resources\FilmographyCandidates\Schemas;

use App\Models\FilmographyCandidate;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

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
                    Placeholder::make('candidate_sources')
                        ->label('Candidate Sources')
                        ->columnSpanFull()
                        ->content(function (?FilmographyCandidate $record): HtmlString {
                            if ($record === null) {
                                return new HtmlString('—');
                            }

                            $urls = $record->candidateSources()->pluck('url');

                            if ($urls->isEmpty()) {
                                return new HtmlString('No Candidate Sources.');
                            }

                            return new HtmlString($urls->map(
                                fn (string $url): string => '<a href="'.e($url).'" target="_blank" rel="noopener noreferrer">'.e($url).'</a>'
                            )->implode('<br>'));
                        }),
                ]),
            ]);
    }
}

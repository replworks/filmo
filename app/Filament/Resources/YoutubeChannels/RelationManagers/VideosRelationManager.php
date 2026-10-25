<?php

namespace App\Filament\Resources\YoutubeChannels\RelationManagers;

use App\Filament\Resources\YoutubeChannels\YoutubeChannelResource;
use App\Jobs\ExtractVideoActors;
use Cable8mm\Youtube\Models\YoutubeVideo;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
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
                TextColumn::make('extraction_status')
                    ->label('Extraction')
                    ->state(fn (YoutubeVideo $video): string => $this->extractionState($video))
                    ->badge()
                    ->color(fn (YoutubeVideo $video): string => $this->extractionColor($video))
                    ->tooltip(fn (YoutubeVideo $video): ?string => $video->extraction_error),
                TextColumn::make('extraction_error')
                    ->label('Extraction error')
                    ->placeholder('—')
                    ->wrap()
                    ->toggleable(),
            ])
            ->defaultSort('published_at', 'desc')
            ->headerActions([
                Action::make('addVideo')
                    ->label('영상 추가')
                    ->icon('heroicon-o-plus')
                    ->schema([
                        TextInput::make('video_id')
                            ->label('Video ID')
                            ->required()
                            ->unique('youtube_videos', 'video_id'),
                        TextInput::make('title')
                            ->label('Title')
                            ->required(),
                        DateTimePicker::make('published_at')
                            ->label('Published at')
                            ->required(),
                        Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->columnSpanFull(),
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
                    ])
                    ->action(function (array $data): void {
                        $this->getOwnerRecord()->videos()->create($data);

                        Notification::make()
                            ->success()
                            ->title('영상을 추가했습니다')
                            ->send();

                        $this->resetTable();
                    }),
            ])
            ->paginated([50])
            ->defaultPaginationPageOption(50)
            ->recordActions([
                Action::make('extract')
                    ->label('Extract Actors')
                    ->icon('heroicon-o-sparkles')
                    ->requiresConfirmation()
                    ->modalHeading('Extract Actors')
                    ->modalDescription('Start a new Gemini Actor Extraction for this Video. Existing Actors and Video associations remain unchanged.')
                    ->action(function (YoutubeVideo $video): void {
                        ExtractVideoActors::dispatchSync($video->id);

                        $video->refresh();

                        if ($video->extraction_status === ExtractVideoActors::STATUS_FAILED) {
                            Notification::make()
                                ->danger()
                                ->title('Gemini Actor Extraction failed')
                                ->body($video->extraction_error)
                                ->send();
                        } else {
                            Notification::make()
                                ->success()
                                ->title('Gemini Actor Extraction completed')
                                ->send();
                        }

                        $this->resetTable();
                    }),
            ])
            ->emptyStateHeading('No Videos')
            ->emptyStateDescription('This Channel has no collected Videos yet.');
    }

    protected function extractionState(YoutubeVideo $video): string
    {
        return match ($video->extraction_status) {
            ExtractVideoActors::STATUS_IN_PROGRESS => 'In progress',
            ExtractVideoActors::STATUS_COMPLETED => 'Completed',
            ExtractVideoActors::STATUS_FAILED => 'Failed',
            default => 'Not started',
        };
    }

    protected function extractionColor(YoutubeVideo $video): string
    {
        return match ($video->extraction_status) {
            ExtractVideoActors::STATUS_IN_PROGRESS => 'info',
            ExtractVideoActors::STATUS_COMPLETED => 'success',
            ExtractVideoActors::STATUS_FAILED => 'danger',
            default => 'gray',
        };
    }
}

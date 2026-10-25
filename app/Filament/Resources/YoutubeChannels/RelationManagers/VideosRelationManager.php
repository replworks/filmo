<?php

namespace App\Filament\Resources\YoutubeChannels\RelationManagers;

use App\Filament\Resources\YoutubeChannels\YoutubeChannelResource;
use App\Jobs\ExtractVideoCandidates;
use Cable8mm\Youtube\Models\YoutubeVideo;
use Filament\Actions\Action;
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
            ->paginated([50])
            ->defaultPaginationPageOption(50)
            ->recordActions([
                Action::make('extract')
                    ->label('Extract Candidates')
                    ->icon('heroicon-o-sparkles')
                    ->requiresConfirmation()
                    ->modalHeading('Extract Actor and Filmography Candidates')
                    ->modalDescription('Start a new Gemini Candidate Extraction for this Video. Candidates from earlier runs remain unchanged.')
                    ->action(function (YoutubeVideo $video): void {
                        ExtractVideoCandidates::dispatchSync($video->id);

                        $video->refresh();

                        if ($video->extraction_status === ExtractVideoCandidates::STATUS_FAILED) {
                            Notification::make()
                                ->danger()
                                ->title('Gemini Candidate Extraction failed')
                                ->body($video->extraction_error)
                                ->send();
                        } else {
                            Notification::make()
                                ->success()
                                ->title('Gemini Candidate Extraction completed')
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
            ExtractVideoCandidates::STATUS_IN_PROGRESS => 'In progress',
            ExtractVideoCandidates::STATUS_COMPLETED => 'Completed',
            ExtractVideoCandidates::STATUS_FAILED => 'Failed',
            default => 'Not started',
        };
    }

    protected function extractionColor(YoutubeVideo $video): string
    {
        return match ($video->extraction_status) {
            ExtractVideoCandidates::STATUS_IN_PROGRESS => 'info',
            ExtractVideoCandidates::STATUS_COMPLETED => 'success',
            ExtractVideoCandidates::STATUS_FAILED => 'danger',
            default => 'gray',
        };
    }
}

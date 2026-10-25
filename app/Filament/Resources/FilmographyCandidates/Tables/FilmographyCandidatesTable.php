<?php

namespace App\Filament\Resources\FilmographyCandidates\Tables;

use App\Filament\Resources\FilmographyCandidates\FilmographyCandidateResource;
use App\Models\FilmographyCandidate;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FilmographyCandidatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('actorCandidate.name')
                    ->label('Actor')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('name')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('video_title')
                    ->label('Video')
                    ->placeholder('—')
                    ->limit(50)
                    ->searchable(),
                TextColumn::make('channel_name')
                    ->label('Channel')
                    ->placeholder('—')
                    ->limit(30)
                    ->searchable(),
                TextColumn::make('review_status')
                    ->label('Review Status')
                    ->badge()
                    ->color(fn (FilmographyCandidate $candidate): string => match ($candidate->review_status) {
                        FilmographyCandidate::REVIEW_STATUS_APPROVED => 'success',
                        FilmographyCandidate::REVIEW_STATUS_REJECTED => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('candidate_source_url')
                    ->label('Source')
                    ->placeholder('—')
                    ->limit(40)
                    ->url(fn (FilmographyCandidate $candidate): ?string => $candidate->candidate_source_url, shouldOpenInNewTab: true),
                TextColumn::make('published_date')
                    ->label('Published')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('review_status', FilmographyCandidate::REVIEW_STATUS_PENDING))
            ->defaultSort('published_date', 'desc')
            ->recordUrl(fn (FilmographyCandidate $candidate): string => FilmographyCandidateResource::getUrl('edit', ['record' => $candidate]))
            ->paginated([50])
            ->defaultPaginationPageOption(50)
            ->recordActions([
                EditAction::make(),
            ])
            ->emptyStateHeading('No Filmography Candidates')
            ->emptyStateDescription('Extract candidates from a Video to create Filmography Candidates.');
    }
}

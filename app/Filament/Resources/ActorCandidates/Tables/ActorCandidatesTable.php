<?php

namespace App\Filament\Resources\ActorCandidates\Tables;

use App\Filament\Resources\ActorCandidates\ActorCandidateResource;
use App\Models\ActorCandidate;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ActorCandidatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->placeholder('—')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('activity_name')
                    ->label('Activity name')
                    ->placeholder('—')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('related_link')
                    ->label('Related Link')
                    ->placeholder('—')
                    ->limit(50)
                    ->url(fn (ActorCandidate $candidate): ?string => $candidate->related_link, shouldOpenInNewTab: true),
                TextColumn::make('filmography_candidates_count')
                    ->label('Filmography Candidates')
                    ->counts('filmographyCandidates')
                    ->numeric(),
                TextColumn::make('candidate_sources_count')
                    ->label('Sources')
                    ->counts('candidateSources')
                    ->numeric(),
            ])
            ->defaultSort('name')
            ->recordUrl(fn (ActorCandidate $candidate): string => ActorCandidateResource::getUrl('view', ['record' => $candidate]))
            ->recordActions([
                ViewAction::make(),
            ])
            ->emptyStateHeading('No Actor Candidates')
            ->emptyStateDescription('Extract candidates from a Video to create Actor Candidates.');
    }
}

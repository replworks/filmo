<?php

namespace App\Filament\Resources\ActorCandidates\Tables;

use App\Filament\Resources\ActorCandidates\ActorCandidateResource;
use App\Models\ActorCandidate;
use App\Services\ActorIdentity\AssociationManager;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

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
                TextColumn::make('actor_identity_id')
                    ->label('Actor Identity')
                    ->state(fn (ActorCandidate $candidate): ?string => $candidate->actor_identity_id === null
                        ? null
                        : 'Actor Identity #'.$candidate->actor_identity_id)
                    ->badge()
                    ->color(fn (ActorCandidate $candidate): string => $candidate->actor_identity_id === null ? 'gray' : 'info')
                    ->placeholder('—'),
            ])
            ->defaultSort('name')
            ->selectable()
            ->recordUrl(fn (ActorCandidate $candidate): string => ActorCandidateResource::getUrl('view', ['record' => $candidate]))
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('confirmIdentity')
                        ->label('Confirm Same Actor')
                        ->icon('heroicon-o-user-group')
                        ->requiresConfirmation()
                        ->modalHeading('Confirm the same Actor')
                        ->modalDescription('The selected Actor Candidates will be associated with one Actor Identity.')
                        ->modalSubmitActionLabel('Confirm')
                        ->action(function (Collection $records): void {
                            try {
                                app(AssociationManager::class)->associate($records->modelKeys(), confirmed: true);

                                Notification::make()
                                    ->success()
                                    ->title('Actor Identity confirmed')
                                    ->send();
                            } catch (Throwable $e) {
                                Notification::make()
                                    ->danger()
                                    ->title('Actor Identity association failed')
                                    ->body($e->getMessage())
                                    ->send();
                            }
                        }),
                ]),
            ])
            ->emptyStateHeading('No Actor Candidates')
            ->emptyStateDescription('Extract candidates from a Video to create Actor Candidates.');
    }
}

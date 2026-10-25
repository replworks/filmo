<?php

namespace App\Filament\Resources\ActorCandidates\Schemas;

use App\Models\ActorCandidate;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ActorCandidateInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('activity_name')
                    ->label('Activity name')
                    ->placeholder('—'),
                TextEntry::make('name')
                    ->placeholder('—'),
                TextEntry::make('related_link')
                    ->label('Related Link')
                    ->placeholder('—'),
                TextEntry::make('filmography_candidates_count')
                    ->label('Filmography Candidates')
                    ->state(fn (ActorCandidate $candidate): int => $candidate->filmographyCandidates()->count()),
            ]);
    }
}

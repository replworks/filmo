<?php

namespace App\Filament\Resources\FilmographyCandidates\Pages;

use App\Filament\Resources\ActorCandidates\ActorCandidateResource;
use App\Filament\Resources\FilmographyCandidates\FilmographyCandidateResource;
use App\Filament\Resources\YoutubeChannels\YoutubeChannelResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListFilmographyCandidates extends ListRecords
{
    protected static string $resource = FilmographyCandidateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('channels')
                ->label('Channel Catalog')
                ->icon('heroicon-o-video-camera')
                ->url(YoutubeChannelResource::getUrl()),
            Action::make('actors')
                ->label('Actor Candidate Sources')
                ->icon('heroicon-o-users')
                ->url(ActorCandidateResource::getUrl()),
        ];
    }
}

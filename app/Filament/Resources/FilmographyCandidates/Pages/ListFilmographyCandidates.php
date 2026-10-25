<?php

namespace App\Filament\Resources\FilmographyCandidates\Pages;

use App\Filament\Resources\FilmographyCandidates\FilmographyCandidateResource;
use Filament\Resources\Pages\ListRecords;

class ListFilmographyCandidates extends ListRecords
{
    protected static string $resource = FilmographyCandidateResource::class;
}

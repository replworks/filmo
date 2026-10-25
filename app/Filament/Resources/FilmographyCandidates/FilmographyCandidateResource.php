<?php

namespace App\Filament\Resources\FilmographyCandidates;

use App\Filament\Resources\FilmographyCandidates\Pages\EditFilmographyCandidate;
use App\Filament\Resources\FilmographyCandidates\Pages\ListFilmographyCandidates;
use App\Filament\Resources\FilmographyCandidates\Schemas\FilmographyCandidateForm;
use App\Filament\Resources\FilmographyCandidates\Tables\FilmographyCandidatesTable;
use App\Models\FilmographyCandidate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class FilmographyCandidateResource extends Resource
{
    protected static ?string $model = FilmographyCandidate::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Filmography Candidates';

    protected static ?string $modelLabel = 'Filmography Candidate';

    protected static ?string $pluralModelLabel = 'Filmography Candidates';

    protected static string|UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 40;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFilm;

    public static function form(Schema $schema): Schema
    {
        return FilmographyCandidateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FilmographyCandidatesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFilmographyCandidates::route('/'),
            'edit' => EditFilmographyCandidate::route('/{record}/edit'),
        ];
    }
}

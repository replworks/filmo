<?php

namespace App\Filament\Resources\ActorCandidates;

use App\Filament\Resources\ActorCandidates\Pages\ListActorCandidates;
use App\Filament\Resources\ActorCandidates\Pages\ViewActorCandidate;
use App\Filament\Resources\ActorCandidates\Schemas\ActorCandidateInfolist;
use App\Filament\Resources\ActorCandidates\Tables\ActorCandidatesTable;
use App\Models\ActorCandidate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ActorCandidateResource extends Resource
{
    protected static ?string $model = ActorCandidate::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Actor Candidates';

    protected static ?string $modelLabel = 'Actor Candidate';

    protected static ?string $pluralModelLabel = 'Actor Candidates';

    protected static string|UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 30;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    public static function infolist(Schema $schema): Schema
    {
        return ActorCandidateInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ActorCandidatesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActorCandidates::route('/'),
            'view' => ViewActorCandidate::route('/{record}'),
        ];
    }
}

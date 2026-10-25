<?php

namespace App\Filament\Resources\Actors;

use App\Filament\Resources\Actors\Pages\EditActor;
use App\Filament\Resources\Actors\Pages\ListActors;
use App\Filament\Resources\Actors\RelationManagers\ActorVideosRelationManager;
use App\Filament\Resources\Actors\Schemas\ActorForm;
use App\Filament\Resources\Actors\Tables\ActorsTable;
use App\Models\Actor;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ActorResource extends Resource
{
    protected static ?string $model = Actor::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Actors';

    protected static ?string $modelLabel = 'Actor';

    protected static ?string $pluralModelLabel = 'Actors';

    protected static string|UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 30;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    public static function form(Schema $schema): Schema
    {
        return ActorForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ActorsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ActorVideosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActors::route('/'),
            'edit' => EditActor::route('/{record}/edit'),
        ];
    }
}

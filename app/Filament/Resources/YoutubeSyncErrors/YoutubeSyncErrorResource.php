<?php

namespace App\Filament\Resources\YoutubeSyncErrors;

use App\Filament\Resources\YoutubeSyncErrors\Pages\ListYoutubeSyncErrors;
use App\Filament\Resources\YoutubeSyncErrors\Pages\ViewYoutubeSyncError;
use App\Filament\Resources\YoutubeSyncErrors\Schemas\YoutubeSyncErrorInfolist;
use App\Filament\Resources\YoutubeSyncErrors\Tables\YoutubeSyncErrorsTable;
use BackedEnum;
use Cable8mm\Youtube\Models\YoutubeSyncError;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class YoutubeSyncErrorResource extends Resource
{
    protected static ?string $model = YoutubeSyncError::class;

    protected static ?string $recordTitleAttribute = 'reason';

    protected static ?string $navigationLabel = 'Sync Errors';

    protected static ?string $modelLabel = 'Sync Error';

    protected static ?string $pluralModelLabel = 'Sync Errors';

    protected static string|UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 22;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    public static function infolist(Schema $schema): Schema
    {
        return YoutubeSyncErrorInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return YoutubeSyncErrorsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListYoutubeSyncErrors::route('/'),
            'view' => ViewYoutubeSyncError::route('/{record}'),
        ];
    }
}

<?php

namespace App\Filament\Resources\YoutubeVideos;

use App\Filament\Resources\YoutubeVideos\Pages\CreateYoutubeVideo;
use App\Filament\Resources\YoutubeVideos\Pages\EditYoutubeVideo;
use App\Filament\Resources\YoutubeVideos\Pages\ListYoutubeVideos;
use App\Filament\Resources\YoutubeVideos\Pages\ViewYoutubeVideo;
use App\Filament\Resources\YoutubeVideos\RelationManagers\VideoActorsRelationManager;
use App\Filament\Resources\YoutubeVideos\Schemas\YoutubeVideoForm;
use App\Filament\Resources\YoutubeVideos\Schemas\YoutubeVideoInfolist;
use App\Filament\Resources\YoutubeVideos\Tables\YoutubeVideosTable;
use App\Jobs\ExtractVideoActors;
use App\Models\Video;
use BackedEnum;
use Cable8mm\Youtube\Models\YoutubeVideo;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class YoutubeVideoResource extends Resource
{
    protected static ?string $model = Video::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $navigationLabel = 'Videos';

    protected static ?string $modelLabel = 'Video';

    protected static ?string $pluralModelLabel = 'Videos';

    protected static string|UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 21;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFilm;

    public static function form(Schema $schema): Schema
    {
        return YoutubeVideoForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return YoutubeVideoInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return YoutubeVideosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            VideoActorsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListYoutubeVideos::route('/'),
            'create' => CreateYoutubeVideo::route('/create'),
            'view' => ViewYoutubeVideo::route('/{record}'),
            'edit' => EditYoutubeVideo::route('/{record}/edit'),
        ];
    }

    public static function videoUrl(YoutubeVideo $video): string
    {
        return 'https://www.youtube.com/watch?v='.$video->video_id;
    }

    public static function availabilityState(YoutubeVideo $video): string
    {
        return $video->unavailable_at === null ? 'Available' : 'Unavailable';
    }

    public static function availabilityColor(YoutubeVideo $video): string
    {
        return $video->unavailable_at === null ? 'success' : 'danger';
    }

    public static function extractionState(YoutubeVideo $video): string
    {
        return match ($video->extraction_status) {
            ExtractVideoActors::STATUS_IN_PROGRESS => 'In progress',
            ExtractVideoActors::STATUS_COMPLETED => 'Completed',
            ExtractVideoActors::STATUS_FAILED => 'Failed',
            default => 'Not extracted',
        };
    }

    public static function extractionColor(YoutubeVideo $video): string
    {
        return match ($video->extraction_status) {
            ExtractVideoActors::STATUS_IN_PROGRESS => 'info',
            ExtractVideoActors::STATUS_COMPLETED => 'success',
            ExtractVideoActors::STATUS_FAILED => 'danger',
            default => 'gray',
        };
    }
}

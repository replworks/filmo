<?php

namespace App\Filament\Resources\YoutubeChannels;

use App\Filament\Resources\YoutubeChannels\Pages\CreateYoutubeChannel;
use App\Filament\Resources\YoutubeChannels\Pages\EditYoutubeChannel;
use App\Filament\Resources\YoutubeChannels\Pages\ListYoutubeChannels;
use App\Filament\Resources\YoutubeChannels\Pages\ViewYoutubeChannel;
use App\Filament\Resources\YoutubeChannels\RelationManagers\VideosRelationManager;
use App\Filament\Resources\YoutubeChannels\Schemas\YoutubeChannelForm;
use App\Filament\Resources\YoutubeChannels\Schemas\YoutubeChannelInfolist;
use App\Filament\Resources\YoutubeChannels\Tables\YoutubeChannelsTable;
use BackedEnum;
use Cable8mm\Youtube\Models\YoutubeChannel;
use Cable8mm\Youtube\Models\YoutubeVideo;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class YoutubeChannelResource extends Resource
{
    protected static ?string $model = YoutubeChannel::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $navigationLabel = 'Channels';

    protected static ?string $modelLabel = 'Channel';

    protected static ?string $pluralModelLabel = 'Channels';

    protected static string|UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 20;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedVideoCamera;

    public static function form(Schema $schema): Schema
    {
        return YoutubeChannelForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return YoutubeChannelInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return YoutubeChannelsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            VideosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListYoutubeChannels::route('/'),
            'create' => CreateYoutubeChannel::route('/create'),
            'view' => ViewYoutubeChannel::route('/{record}'),
            'edit' => EditYoutubeChannel::route('/{record}/edit'),
        ];
    }

    public static function collectionState(YoutubeChannel $channel): string
    {
        return match (true) {
            $channel->sync_status === 'paused' || $channel->last_error_reason === 'quota_exceeded' => 'Paused (quota exhausted)',
            $channel->sync_status === 'failed' => $channel->last_error_reason === null
                ? 'Failed'
                : 'Failed ('.$channel->last_error_reason.')',
            $channel->sync_status === 'ok' => 'OK',
            default => 'Not collected yet',
        };
    }

    public static function collectionColor(YoutubeChannel $channel): string
    {
        return match (true) {
            $channel->sync_status === 'paused' || $channel->last_error_reason === 'quota_exceeded' => 'warning',
            $channel->sync_status === 'failed' => 'danger',
            $channel->sync_status === 'ok' => 'success',
            default => 'gray',
        };
    }

    public static function videoUrl(YoutubeVideo $video): string
    {
        return 'https://www.youtube.com/watch?v='.$video->video_id;
    }
}

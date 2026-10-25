<?php

namespace App\Filament\Resources\YoutubeChannels\Pages;

use App\Filament\Pages\ChannelImport;
use App\Filament\Resources\Actors\ActorResource;
use App\Filament\Resources\YoutubeChannels\YoutubeChannelResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListYoutubeChannels extends ListRecords
{
    protected static string $resource = YoutubeChannelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('New Channel'),
            Action::make('import')
                ->label('Import Channels')
                ->icon('heroicon-o-arrow-up-tray')
                ->url(ChannelImport::getUrl()),
            Action::make('actors')
                ->label('Actors')
                ->icon('heroicon-o-users')
                ->url(ActorResource::getUrl()),
        ];
    }
}

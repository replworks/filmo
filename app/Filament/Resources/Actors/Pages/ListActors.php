<?php

namespace App\Filament\Resources\Actors\Pages;

use App\Filament\Resources\Actors\ActorResource;
use App\Filament\Resources\YoutubeChannels\YoutubeChannelResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListActors extends ListRecords
{
    protected static string $resource = ActorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('New Actor'),
            Action::make('channels')
                ->label('Channel Catalog')
                ->icon('heroicon-o-video-camera')
                ->url(YoutubeChannelResource::getUrl()),
        ];
    }
}

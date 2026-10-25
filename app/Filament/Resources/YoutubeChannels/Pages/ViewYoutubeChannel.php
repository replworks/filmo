<?php

namespace App\Filament\Resources\YoutubeChannels\Pages;

use App\Filament\Pages\ChannelImport;
use App\Filament\Resources\YoutubeChannels\YoutubeChannelResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewYoutubeChannel extends ViewRecord
{
    protected static string $resource = YoutubeChannelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('import')
                ->label('Import Channels')
                ->icon('heroicon-o-arrow-up-tray')
                ->url(ChannelImport::getUrl()),
        ];
    }
}

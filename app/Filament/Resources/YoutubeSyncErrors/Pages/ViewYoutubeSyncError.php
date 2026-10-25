<?php

namespace App\Filament\Resources\YoutubeSyncErrors\Pages;

use App\Filament\Resources\YoutubeSyncErrors\YoutubeSyncErrorResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;

class ViewYoutubeSyncError extends ViewRecord
{
    protected static string $resource = YoutubeSyncErrorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->successRedirectUrl(YoutubeSyncErrorResource::getUrl()),
        ];
    }
}

<?php

namespace App\Filament\Resources\YoutubeChannels\Pages;

use App\Filament\Resources\YoutubeChannels\YoutubeChannelResource;
use Filament\Resources\Pages\CreateRecord;

class CreateYoutubeChannel extends CreateRecord
{
    protected static string $resource = YoutubeChannelResource::class;

    protected function getRedirectUrl(): string
    {
        return YoutubeChannelResource::getUrl('view', ['record' => $this->getRecord()]);
    }
}

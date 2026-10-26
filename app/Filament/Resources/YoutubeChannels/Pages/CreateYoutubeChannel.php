<?php

namespace App\Filament\Resources\YoutubeChannels\Pages;

use App\Filament\Resources\YoutubeChannels\YoutubeChannelResource;
use App\Support\CatalogOwner;
use Cable8mm\Youtube\Models\YoutubeChannel;
use Filament\Resources\Pages\CreateRecord;

class CreateYoutubeChannel extends CreateRecord
{
    protected static string $resource = YoutubeChannelResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = CatalogOwner::id();
        $data['is_public'] = true;

        return $data;
    }

    protected function handleRecordCreation(array $data): YoutubeChannel
    {
        $record = parent::handleRecordCreation($data);
        $record->user_id = CatalogOwner::id();
        $record->is_public = true;
        $record->save();

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return YoutubeChannelResource::getUrl('view', ['record' => $this->getRecord()]);
    }
}

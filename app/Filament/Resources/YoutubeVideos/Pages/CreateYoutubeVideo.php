<?php

namespace App\Filament\Resources\YoutubeVideos\Pages;

use App\Filament\Resources\YoutubeVideos\YoutubeVideoResource;
use App\Support\CatalogOwner;
use Cable8mm\Youtube\Models\YoutubeChannel;
use Filament\Resources\Pages\CreateRecord;

class CreateYoutubeVideo extends CreateRecord
{
    protected static string $resource = YoutubeVideoResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $channel = YoutubeChannel::find($data['youtube_channel_id'] ?? null);
        $data['user_id'] = $channel?->user_id ?? CatalogOwner::id();
        $data['is_public'] = true;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return YoutubeVideoResource::getUrl('view', ['record' => $this->getRecord()]);
    }
}

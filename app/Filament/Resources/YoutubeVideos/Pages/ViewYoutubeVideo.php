<?php

namespace App\Filament\Resources\YoutubeVideos\Pages;

use App\Filament\Resources\YoutubeVideos\YoutubeVideoResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewYoutubeVideo extends ViewRecord
{
    protected static string $resource = YoutubeVideoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make()
                ->modalDescription('이 영상과 배우-영상 연결이 함께 삭제됩니다.')
                ->successRedirectUrl(YoutubeVideoResource::getUrl()),
        ];
    }
}

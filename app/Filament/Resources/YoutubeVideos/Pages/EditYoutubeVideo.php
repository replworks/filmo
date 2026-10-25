<?php

namespace App\Filament\Resources\YoutubeVideos\Pages;

use App\Filament\Resources\YoutubeVideos\YoutubeVideoResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditYoutubeVideo extends EditRecord
{
    protected static string $resource = YoutubeVideoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->modalDescription('이 영상과 배우-영상 연결이 함께 삭제됩니다.')
                ->successRedirectUrl(YoutubeVideoResource::getUrl()),
        ];
    }
}

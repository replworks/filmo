<?php

namespace App\Filament\Resources\YoutubeChannels\Pages;

use App\Filament\Resources\YoutubeChannels\YoutubeChannelResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditYoutubeChannel extends EditRecord
{
    protected static string $resource = YoutubeChannelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->modalDescription('이 채널의 모든 영상과 배우-영상 연결이 함께 삭제됩니다.')
                ->successRedirectUrl(YoutubeChannelResource::getUrl()),
        ];
    }
}

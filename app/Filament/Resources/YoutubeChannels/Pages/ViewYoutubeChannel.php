<?php

namespace App\Filament\Resources\YoutubeChannels\Pages;

use App\Filament\Pages\ChannelImport;
use App\Filament\Resources\Actors\ActorResource;
use App\Filament\Resources\YoutubeChannels\YoutubeChannelResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewYoutubeChannel extends ViewRecord
{
    protected static string $resource = YoutubeChannelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make()
                ->modalDescription('이 채널의 모든 영상과 배우-영상 연결이 함께 삭제됩니다.')
                ->successRedirectUrl(YoutubeChannelResource::getUrl()),
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

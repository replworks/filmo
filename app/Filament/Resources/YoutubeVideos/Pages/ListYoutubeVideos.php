<?php

namespace App\Filament\Resources\YoutubeVideos\Pages;

use App\Filament\Resources\YoutubeVideos\YoutubeVideoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListYoutubeVideos extends ListRecords
{
    protected static string $resource = YoutubeVideoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('New Video'),
        ];
    }
}

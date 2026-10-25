<?php

namespace App\Filament\Resources\Actors\Pages;

use App\Filament\Resources\Actors\ActorResource;
use Filament\Resources\Pages\CreateRecord;

class CreateActor extends CreateRecord
{
    protected static string $resource = ActorResource::class;

    protected function getRedirectUrl(): string
    {
        return ActorResource::getUrl('edit', ['record' => $this->getRecord()]);
    }
}

<?php

namespace App\Filament\Resources\Actors\Pages;

use App\Filament\Resources\Actors\ActorResource;
use App\Support\CatalogOwner;
use Filament\Resources\Pages\CreateRecord;

class CreateActor extends CreateRecord
{
    protected static string $resource = ActorResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = CatalogOwner::id();
        $data['is_public'] = true;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return ActorResource::getUrl('edit', ['record' => $this->getRecord()]);
    }
}

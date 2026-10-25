<?php

namespace App\Filament\Resources\Actors\Pages;

use App\Filament\Resources\Actors\ActorResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;
use Throwable;

class EditActor extends EditRecord
{
    protected static string $resource = ActorResource::class;

    public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    {
        try {
            parent::save($shouldRedirect, $shouldSendSavedNotification);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Notification::make()
                ->danger()
                ->title('배우 저장 실패')
                ->body($e->getMessage())
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('openUrl')
                ->label('배우 URL 열기')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url(fn (): ?string => $this->record->url)
                ->openUrlInNewTab()
                ->visible(fn (): bool => filled($this->record->url)),
        ];
    }
}

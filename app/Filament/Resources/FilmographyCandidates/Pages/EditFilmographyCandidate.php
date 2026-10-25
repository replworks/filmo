<?php

namespace App\Filament\Resources\FilmographyCandidates\Pages;

use App\Filament\Resources\FilmographyCandidates\FilmographyCandidateResource;
use App\Models\CandidateSource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Throwable;

class EditFilmographyCandidate extends EditRecord
{
    protected static string $resource = FilmographyCandidateResource::class;

    public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    {
        try {
            parent::save($shouldRedirect, $shouldSendSavedNotification);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Notification::make()
                ->danger()
                ->title('Candidate save failed')
                ->body($e->getMessage())
                ->send();
        }
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $sourceUrl = trim((string) ($data['candidate_source_url'] ?? ''));

        $record->update($data);

        if ($sourceUrl !== '') {
            $record->candidateSources()->firstOrCreate([
                'kind' => CandidateSource::KIND_MANUAL,
                'url' => $sourceUrl,
            ]);
        }

        return $record;
    }
}

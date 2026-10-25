<?php

namespace App\Filament\Resources\FilmographyCandidates\Pages;

use App\Filament\Resources\FilmographyCandidates\FilmographyCandidateResource;
use App\Models\CandidateSource;
use App\Models\FilmographyCandidate;
use App\Services\Filmography\ReviewManager;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Throwable;

class EditFilmographyCandidate extends EditRecord
{
    protected static string $resource = FilmographyCandidateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Approve')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->visible(fn (): bool => $this->isPending())
                ->requiresConfirmation()
                ->schema([
                    Textarea::make('review_note')
                        ->label('Review note (optional)')
                        ->rows(3),
                ])
                ->action(fn (array $data): mixed => $this->review(FilmographyCandidate::REVIEW_STATUS_APPROVED, $data['review_note'] ?? null)),
            Action::make('reject')
                ->label('Reject')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->visible(fn (): bool => $this->isPending())
                ->requiresConfirmation()
                ->schema([
                    Textarea::make('review_note')
                        ->label('Review note (optional)')
                        ->rows(3),
                ])
                ->action(fn (array $data): mixed => $this->review(FilmographyCandidate::REVIEW_STATUS_REJECTED, $data['review_note'] ?? null)),
        ];
    }

    protected function review(string $status, ?string $note): void
    {
        try {
            $reviewer = auth()->user()?->name ?? 'Operator';
            $manager = app(ReviewManager::class);

            $status === FilmographyCandidate::REVIEW_STATUS_APPROVED
                ? $manager->approve($this->record->id, $reviewer, $note)
                : $manager->reject($this->record->id, $reviewer, $note);

            Notification::make()
                ->success()
                ->title($status === FilmographyCandidate::REVIEW_STATUS_APPROVED
                    ? 'Filmography Candidate approved'
                    : 'Filmography Candidate rejected')
                ->send();

            $this->redirect(FilmographyCandidateResource::getUrl('index'));
        } catch (Throwable $e) {
            Notification::make()
                ->danger()
                ->title('Review failed')
                ->body($e->getMessage())
                ->send();
        }
    }

    protected function isPending(): bool
    {
        return $this->record->review_status === FilmographyCandidate::REVIEW_STATUS_PENDING;
    }

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

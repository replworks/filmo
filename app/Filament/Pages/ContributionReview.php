<?php

namespace App\Filament\Pages;

use App\Models\Contribution;
use App\Services\Contribution\ContributionManager;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use RuntimeException;
use UnitEnum;

class ContributionReview extends Page
{
    protected static ?string $title = 'Contribution Review';

    protected static ?string $navigationLabel = 'Contributions';

    protected static string|UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 25;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    public function content(Schema $schema): Schema
    {
        $pending = $this->pendingContributions();

        if ($pending->isEmpty()) {
            return $schema->components([
                Text::make('No pending contributions.')->color('gray'),
            ]);
        }

        return $schema->components(
            $pending->map(fn (Contribution $contribution): Section => $this->contributionSection($contribution))->all(),
        );
    }

    public function approve(int $contributionId, ?string $reviewNote = null): void
    {
        $contribution = Contribution::findOrFail($contributionId);

        try {
            app(ContributionManager::class)->approve($contribution, auth()->user(), $reviewNote);

            Notification::make()
                ->success()
                ->title('Contribution approved')
                ->send();
        } catch (RuntimeException $e) {
            Notification::make()
                ->danger()
                ->title('Contribution could not be approved')
                ->body($e->getMessage())
                ->send();
        }
    }

    public function reject(int $contributionId, ?string $reviewNote = null): void
    {
        $contribution = Contribution::findOrFail($contributionId);

        try {
            app(ContributionManager::class)->reject($contribution, auth()->user(), $reviewNote);

            Notification::make()
                ->success()
                ->title('Contribution rejected')
                ->send();
        } catch (RuntimeException $e) {
            Notification::make()
                ->danger()
                ->title('Contribution could not be rejected')
                ->body($e->getMessage())
                ->send();
        }
    }

    /**
     * @return Collection<int, Contribution>
     */
    private function pendingContributions(): Collection
    {
        return Contribution::query()
            ->with(['user', 'contributable'])
            ->where('status', Contribution::STATUS_PENDING)
            ->oldest()
            ->get();
    }

    private function contributionSection(Contribution $contribution): Section
    {
        $record = $contribution->contributable;
        $title = match (true) {
            $record === null => 'Missing record',
            default => $record->title ?? $record->name ?? 'Record #'.$contribution->contributable_id,
        };

        return Section::make('#'.$contribution->id.' · '.$title)
            ->description(class_basename((string) $contribution->contributable_type))
            ->schema([
                Text::make('Contributor: '.($contribution->user?->name ?? 'Unknown').' <'.$contribution->user?->email.'>'),
                Text::make(json_encode($contribution->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '{}')
                    ->columnSpanFull(),
                Actions::make([
                    Action::make('approve_'.$contribution->id)
                        ->label('Approve')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->schema($this->reviewNoteSchema())
                        ->action(fn (array $data): mixed => $this->approve($contribution->id, $data['review_note'] ?? null)),
                    Action::make('reject_'.$contribution->id)
                        ->label('Reject')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->schema($this->reviewNoteSchema())
                        ->action(fn (array $data): mixed => $this->reject($contribution->id, $data['review_note'] ?? null)),
                ]),
            ]);
    }

    /**
     * @return array<int, Textarea>
     */
    private function reviewNoteSchema(): array
    {
        return [
            Textarea::make('review_note')
                ->label('Review note')
                ->rows(3),
        ];
    }
}

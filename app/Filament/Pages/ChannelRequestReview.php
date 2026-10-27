<?php

namespace App\Filament\Pages;

use App\Models\ChannelRequest;
use App\Services\ChannelRequest\ChannelRequestManager;
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
use Throwable;
use UnitEnum;

class ChannelRequestReview extends Page
{
    protected static ?string $title = 'Channel Request Review';

    protected static ?string $navigationLabel = 'Channel Requests';

    protected static string|UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 24;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    public function content(Schema $schema): Schema
    {
        $pending = $this->pendingRequests();

        if ($pending->isEmpty()) {
            return $schema->components([
                Text::make('No pending channel requests.')->color('gray'),
            ]);
        }

        return $schema->components(
            $pending->map(fn (ChannelRequest $request): Section => $this->requestSection($request))->all(),
        );
    }

    public function approve(int $requestId, ?string $reviewNote = null): void
    {
        $request = ChannelRequest::findOrFail($requestId);

        try {
            app(ChannelRequestManager::class)->approve($request, auth()->user(), $reviewNote);

            Notification::make()
                ->success()
                ->title('Channel request approved')
                ->send();
        } catch (Throwable $e) {
            Notification::make()
                ->danger()
                ->title('Channel request could not be approved')
                ->body($e->getMessage())
                ->send();
        }
    }

    public function reject(int $requestId, ?string $reviewNote = null): void
    {
        $request = ChannelRequest::findOrFail($requestId);

        try {
            app(ChannelRequestManager::class)->reject($request, auth()->user(), $reviewNote);

            Notification::make()
                ->success()
                ->title('Channel request rejected')
                ->send();
        } catch (RuntimeException $e) {
            Notification::make()
                ->danger()
                ->title('Channel request could not be rejected')
                ->body($e->getMessage())
                ->send();
        }
    }

    /**
     * @return Collection<int, ChannelRequest>
     */
    private function pendingRequests(): Collection
    {
        return ChannelRequest::query()
            ->with('user')
            ->where('status', ChannelRequest::STATUS_PENDING)
            ->oldest()
            ->get();
    }

    private function requestSection(ChannelRequest $request): Section
    {
        return Section::make('#'.$request->id.' · '.$request->input)
            ->description('Channel request')
            ->schema([
                Text::make('Requester: '.($request->user?->name ?? 'Unknown').' <'.$request->user?->email.'>'),
                Actions::make([
                    Action::make('approve_'.$request->id)
                        ->label('Approve')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->schema([
                            Textarea::make('review_note')
                                ->label('Review note')
                                ->rows(3),
                        ])
                        ->action(fn (array $data): mixed => $this->approve($request->id, $data['review_note'] ?? null)),
                    Action::make('reject_'.$request->id)
                        ->label('Reject')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->schema([
                            Textarea::make('review_note')
                                ->label('Review note')
                                ->rows(3),
                        ])
                        ->action(fn (array $data): mixed => $this->reject($request->id, $data['review_note'] ?? null)),
                ]),
            ]);
    }
}

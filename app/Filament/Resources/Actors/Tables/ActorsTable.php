<?php

namespace App\Filament\Resources\Actors\Tables;

use App\Filament\Resources\Actors\ActorResource;
use App\Models\Actor;
use App\Services\Actor\MergeManager;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

class ActorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereNull('merged_into_actor_id'))
            ->columns([
                TextColumn::make('name')
                    ->label('이름')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('activity_name')
                    ->label('활동명')
                    ->placeholder('—')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('url')
                    ->label('배우 URL')
                    ->placeholder('—')
                    ->limit(45)
                    ->url(fn (Actor $actor): ?string => $actor->url, shouldOpenInNewTab: true),
                TextColumn::make('videos_count')
                    ->label('출연 영상')
                    ->counts('videos')
                    ->numeric()
                    ->sortable(),
            ])
            ->defaultSort('name')
            ->selectable()
            ->recordUrl(fn (Actor $actor): string => static::editUrl($actor))
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('merge')
                        ->label('같은 배우로 합치기')
                        ->icon('heroicon-o-user-group')
                        ->requiresConfirmation()
                        ->modalHeading('같은 배우로 합치기')
                        ->modalDescription('선택한 배우를 하나로 합칩니다. 출연 영상은 대표 배우에게로 이동합니다.')
                        ->modalSubmitActionLabel('합치기')
                        ->schema([
                            Select::make('survivor_id')
                                ->label('대표 배우')
                                ->options(fn (): array => Actor::query()
                                    ->active()
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all())
                                ->searchable()
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            try {
                                app(MergeManager::class)->merge(
                                    $records->modelKeys(),
                                    (int) $data['survivor_id'],
                                    confirmed: true,
                                );

                                Notification::make()
                                    ->success()
                                    ->title('배우를 합쳤습니다')
                                    ->send();
                            } catch (Throwable $e) {
                                Notification::make()
                                    ->danger()
                                    ->title('배우 합치기 실패')
                                    ->body($e->getMessage())
                                    ->send();
                            }
                        }),
                ]),
            ])
            ->emptyStateHeading('등록된 배우가 없습니다')
            ->emptyStateDescription('영상에서 배우를 추출하면 이곳에 표시됩니다.');
    }

    protected static function editUrl(Actor $actor): string
    {
        return ActorResource::getUrl('edit', ['record' => $actor]);
    }
}

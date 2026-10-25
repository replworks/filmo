<?php

namespace App\Filament\Resources\YoutubeVideos\RelationManagers;

use App\Models\Actor;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VideoActorsRelationManager extends RelationManager
{
    protected static string $relationship = 'actors';

    protected static ?string $title = '출연 배우';

    protected static ?string $recordTitleAttribute = 'name';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label('이름')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('activity_name')
                    ->label('활동명')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('birth_date')
                    ->label('생년월일')
                    ->date()
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('reference_urls_count')
                    ->label('URL')
                    ->counts('referenceUrls')
                    ->numeric(),
            ])
            ->defaultSort('name')
            ->headerActions([
                Action::make('addActor')
                    ->label('배우 추가')
                    ->icon('heroicon-o-plus')
                    ->schema([
                        Select::make('actor_id')
                            ->label('배우')
                            ->options(fn (): array => Actor::query()
                                ->active()
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        $this->getOwnerRecord()->actors()->syncWithoutDetaching([(int) $data['actor_id']]);

                        Notification::make()
                            ->success()
                            ->title('출연 배우를 추가했습니다')
                            ->send();

                        $this->resetTable();
                    }),
            ])
            ->recordActions([
                Action::make('removeActor')
                    ->label('연결 해제')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Actor $actor): void {
                        $this->getOwnerRecord()->actors()->detach($actor->id);

                        Notification::make()
                            ->success()
                            ->title('출연 배우 연결을 해제했습니다')
                            ->send();

                        $this->resetTable();
                    }),
            ])
            ->emptyStateHeading('출연 배우가 없습니다')
            ->emptyStateDescription('배우를 추가하거나 영상에서 배우를 추출하세요.');
    }
}

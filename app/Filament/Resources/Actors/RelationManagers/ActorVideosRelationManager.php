<?php

namespace App\Filament\Resources\Actors\RelationManagers;

use Cable8mm\Youtube\Models\YoutubeVideo;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ActorVideosRelationManager extends RelationManager
{
    protected static string $relationship = 'videos';

    protected static ?string $title = '출연 영상';

    protected static ?string $recordTitleAttribute = 'title';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('title')
                    ->label('영상 제목')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('channel.title')
                    ->label('채널')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('published_at')
                    ->label('공개일')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('youtube_url')
                    ->label('YouTube URL')
                    ->state(fn (YoutubeVideo $video): string => $this->videoUrl($video))
                    ->url(fn (YoutubeVideo $video): string => $this->videoUrl($video), shouldOpenInNewTab: true)
                    ->limit(50),
            ])
            ->defaultSort('published_at', 'desc')
            ->paginated([50])
            ->defaultPaginationPageOption(50)
            ->headerActions([
                Action::make('addVideo')
                    ->label('영상 추가')
                    ->icon('heroicon-o-plus')
                    ->schema([
                        Select::make('video_id')
                            ->label('영상')
                            ->options(fn (): array => YoutubeVideo::query()
                                ->orderByDesc('published_at')
                                ->limit(500)
                                ->pluck('title', 'id')
                                ->all())
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        $this->getOwnerRecord()->videos()->syncWithoutDetaching([(int) $data['video_id']]);

                        Notification::make()
                            ->success()
                            ->title('출연 영상을 추가했습니다')
                            ->send();
                    }),
            ])
            ->recordActions([
                Action::make('removeVideo')
                    ->label('연결 해제')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (YoutubeVideo $video): void {
                        $this->getOwnerRecord()->videos()->detach($video->id);

                        Notification::make()
                            ->success()
                            ->title('출연 영상 연결을 해제했습니다')
                            ->send();
                    }),
            ])
            ->emptyStateHeading('출연 영상이 없습니다')
            ->emptyStateDescription('영상에서 배우를 추출하거나 영상을 직접 추가하세요.');
    }

    protected function videoUrl(YoutubeVideo $video): string
    {
        return 'https://www.youtube.com/watch?v='.$video->video_id;
    }
}

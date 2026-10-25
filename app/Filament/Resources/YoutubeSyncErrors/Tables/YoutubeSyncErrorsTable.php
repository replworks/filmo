<?php

namespace App\Filament\Resources\YoutubeSyncErrors\Tables;

use App\Filament\Resources\YoutubeSyncErrors\YoutubeSyncErrorResource;
use Cable8mm\Youtube\Models\YoutubeSyncError;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class YoutubeSyncErrorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Occurred at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('channel.title')
                    ->label('Channel')
                    ->placeholder('—')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('reason')
                    ->label('Reason')
                    ->searchable()
                    ->badge(),
                TextColumn::make('http_code')
                    ->label('HTTP code')
                    ->numeric()
                    ->placeholder('—'),
                TextColumn::make('message')
                    ->label('Message')
                    ->limit(70)
                    ->wrap(),
            ])
            ->filters([
                SelectFilter::make('youtube_channel_id')
                    ->label('Channel')
                    ->relationship('channel', 'title')
                    ->searchable(),
                SelectFilter::make('reason')
                    ->label('Reason')
                    ->options(fn (): array => YoutubeSyncError::query()
                        ->distinct()
                        ->orderBy('reason')
                        ->pluck('reason', 'reason')
                        ->all()),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn (YoutubeSyncError $error): string => YoutubeSyncErrorResource::getUrl('view', ['record' => $error]))
            ->recordActions([
                ViewAction::make(),
                DeleteAction::make(),
            ])
            ->emptyStateHeading('No Sync Errors')
            ->emptyStateDescription('Channel synchronization failures appear here.')
            ->emptyStateIcon('heroicon-o-exclamation-triangle');
    }
}

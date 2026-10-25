<?php

namespace App\Filament\Resources\Actors\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ActorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Actor')->schema([
                    TextInput::make('name')
                        ->label('이름')
                        ->required(),
                    TextInput::make('activity_name')
                        ->label('활동명'),
                    TextInput::make('url')
                        ->label('배우 URL')
                        ->helperText('NamuWiki 또는 Naver 배우 정보 URL')
                        ->url(),
                ]),
                Section::make('추가 정보')->schema([
                    KeyValue::make('profile')
                        ->label('프로필')
                        ->keyLabel('항목')
                        ->valueLabel('값')
                        ->addActionLabel('항목 추가')
                        ->columnSpanFull(),
                ]),
            ]);
    }
}

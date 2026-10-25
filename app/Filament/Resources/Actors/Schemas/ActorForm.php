<?php

namespace App\Filament\Resources\Actors\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
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
                ]),
                Section::make('배우 URL')->schema([
                    Repeater::make('referenceUrls')
                        ->relationship()
                        ->label('URL 목록')
                        ->schema([
                            TextInput::make('url')
                                ->label('URL')
                                ->placeholder('NamuWiki, Naver 등')
                                ->required()
                                ->url()
                                ->columnSpanFull(),
                        ])
                        ->addActionLabel('URL 추가')
                        ->defaultItems(0)
                        ->columnSpanFull(),
                ]),
                Section::make('배우 정보')->schema([
                    DatePicker::make('birth_date')
                        ->label('생년월일'),
                    TextInput::make('birth_place')
                        ->label('출생지'),
                    Textarea::make('education')
                        ->label('학력 / 졸업 학교')
                        ->rows(2),
                    TextInput::make('debut')
                        ->label('데뷔'),
                    TextInput::make('agency')
                        ->label('소속사'),
                    TextInput::make('nationality')
                        ->label('국적'),
                ])->columns(2),
                Section::make('기타 정보')->schema([
                    KeyValue::make('profile')
                        ->label('기타 항목')
                        ->keyLabel('항목')
                        ->valueLabel('값')
                        ->addActionLabel('항목 추가')
                        ->helperText('위 필드에 없는 추가 정보만 입력하세요.')
                        ->columnSpanFull(),
                ]),
            ]);
    }
}

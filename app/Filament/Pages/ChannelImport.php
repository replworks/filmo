<?php

namespace App\Filament\Pages;

use App\Filament\Resources\YoutubeChannels\YoutubeChannelResource;
use BackedEnum;
use Cable8mm\Youtube\Imports\YoutubeChannelsImport;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ChannelImport extends Page
{
    public const EXTENSIONS = ['csv', 'xlsx', 'xls'];

    protected static ?string $title = 'Channel Import';

    protected static ?string $navigationLabel = 'Import Channels';

    protected static ?string $slug = 'channel-import';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?int $navigationSort = 10;

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    /**
     * @var array<int, array{row: int, input: string, result: string}> | null
     */
    public ?array $results = null;

    public ?string $importError = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('channels')
                ->label('Channel List')
                ->icon('heroicon-o-list-bullet')
                ->url(YoutubeChannelResource::getUrl()),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('spreadsheet')
                    ->label('YouTube Channel Spreadsheet')
                    ->helperText('An .xlsx, .xls, or .csv file with a channel column.')
                    ->acceptedFileTypes([
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-excel',
                        'text/csv',
                        'text/plain',
                    ])
                    ->rules(['extensions:xlsx,xls,csv'])
                    ->storeFiles(false)
                    ->required(),
            ])
            ->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        $components = [
            Form::make([EmbeddedSchema::make('form')])
                ->id('channel-import-form')
                ->livewireSubmitHandler('import')
                ->footer([
                    Actions::make([
                        Action::make('import')
                            ->label('Import')
                            ->icon('heroicon-o-arrow-up-tray')
                            ->submit('import'),
                    ]),
                ]),
        ];

        if ($this->importError !== null) {
            $components[] = Callout::make('Import failed')
                ->danger()
                ->description($this->importError);
        }

        if ($this->results !== null) {
            $components[] = Section::make('Import Results')
                ->schema($this->resultComponents());
        }

        return $schema->components($components);
    }

    public function import(): void
    {
        $file = $this->form->getState()['spreadsheet'] ?? null;

        if (! $file instanceof TemporaryUploadedFile) {
            $this->importError = 'Select a spreadsheet to import.';
            $this->results = null;

            return;
        }

        $path = $file->getRealPath();

        if (! is_string($path) || $path === '') {
            $this->importError = 'The selected spreadsheet could not be read.';
            $this->results = null;

            return;
        }

        $this->importSpreadsheet($path, strtolower($file->getClientOriginalExtension()));
    }

    protected function importSpreadsheet(string $path, string $extension): void
    {
        $this->results = null;
        $this->importError = null;

        if (! in_array($extension, self::EXTENSIONS, true)) {
            $this->importError = 'Unsupported file type: use csv, xlsx, or xls.';

            return;
        }

        $import = $this->makeImport();

        try {
            Excel::import($import, $path);
        } catch (Throwable $e) {
            $this->importError = 'The spreadsheet could not be processed: '.$e->getMessage();
        }

        $this->results = $import->results();

        if ($this->importError === null && ! $import->hasChannelColumn()) {
            $this->importError = 'The spreadsheet has no channel column.';
        }
    }

    protected function makeImport(): YoutubeChannelsImport
    {
        return new YoutubeChannelsImport;
    }

    /**
     * @return array<int, Text>
     */
    protected function resultComponents(): array
    {
        if ($this->results === []) {
            return [Text::make('No rows were processed.')->color('gray')];
        }

        return array_map(function (array $row): Text {
            $failed = str_starts_with($row['result'], 'failed: ') || str_starts_with($row['result'], 'skipped: ');
            $input = $row['input'] === '' ? '(empty)' : $row['input'];

            return Text::make("Row {$row['row']} · {$input} · {$row['result']}")
                ->color($failed ? 'danger' : 'success');
        }, $this->results);
    }
}

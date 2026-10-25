<?php

namespace App\Filament\Resources\ActorCandidates\Pages;

use App\Filament\Resources\ActorCandidates\ActorCandidateResource;
use App\Models\CandidateSource;
use App\Services\NamuWiki\ActorInformationExtractor;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;
use Throwable;

class ViewActorCandidate extends ViewRecord
{
    protected static string $resource = ActorCandidateResource::class;

    /**
     * @var array<string, mixed>
     */
    public ?array $namuWikiData = [];

    /**
     * @var array<string, mixed> | null
     */
    public ?array $suggestions = null;

    public ?string $extractionError = null;

    /**
     * @var array<string, mixed>
     */
    public ?array $naverData = [];

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->namuWikiForm->fill([
            'url' => $this->record->candidateSources()
                ->where('kind', CandidateSource::KIND_NAMUWIKI)
                ->latest('id')
                ->value('url'),
        ]);

        $this->naverForm->fill([
            'activity_name' => $this->record->activity_name,
            'name' => $this->record->name,
            'related_link' => $this->record->related_link,
            'url' => $this->record->candidateSources()
                ->where('kind', CandidateSource::KIND_NAVER)
                ->latest('id')
                ->value('url'),
        ]);
    }

    public function naverForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('activity_name')
                    ->label('Activity Name'),
                TextInput::make('name')
                    ->label('Name'),
                TextInput::make('related_link')
                    ->label('Related Link')
                    ->url(),
                TextInput::make('url')
                    ->label('Naver Person Information URL')
                    ->helperText('An https://...naver.com page the Operator opens for manual research.')
                    ->url()
                    ->live(),
            ])
            ->statePath('naverData');
    }

    public function saveNaver(): void
    {
        $data = $this->naverForm->getState();
        $url = trim((string) ($data['url'] ?? ''));

        if ($url !== '' && ! $this->isNaverUrl($url)) {
            $this->addError('naverData.url', 'Enter a Naver person information URL.');

            return;
        }

        try {
            DB::transaction(function () use ($data, $url): void {
                $this->record->update([
                    'activity_name' => $this->nullIfBlank($data['activity_name'] ?? null),
                    'name' => $this->nullIfBlank($data['name'] ?? null),
                    'related_link' => $this->nullIfBlank($data['related_link'] ?? null),
                ]);

                if ($url !== '') {
                    $this->record->candidateSources()->firstOrCreate([
                        'kind' => CandidateSource::KIND_NAVER,
                        'url' => $url,
                    ]);
                }
            });
        } catch (Throwable $e) {
            Notification::make()
                ->danger()
                ->title('Actor Candidate save failed')
                ->body($e->getMessage())
                ->send();

            return;
        }

        $this->record->refresh();

        $this->naverForm->fill([
            'activity_name' => $this->record->activity_name,
            'name' => $this->record->name,
            'related_link' => $this->record->related_link,
            'url' => $url,
        ]);

        Notification::make()
            ->success()
            ->title('Actor Candidate saved')
            ->send();
    }

    public function naverUrl(): ?string
    {
        $url = trim((string) ($this->naverData['url'] ?? ''));

        return $url === '' ? null : $url;
    }

    protected function isNaverUrl(string $url): bool
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = parse_url($url, PHP_URL_HOST);

        return in_array($scheme, ['http', 'https'], true)
            && is_string($host)
            && ($host === 'naver.com' || str_ends_with($host, '.naver.com'));
    }

    protected function nullIfBlank(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    public function namuWikiForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('url')
                    ->label('NamuWiki URL')
                    ->helperText('An https://namu.wiki/w/... page for this Actor Candidate.')
                    ->url()
                    ->required(),
            ])
            ->statePath('namuWikiData');
    }

    public function extractNamuWiki(): void
    {
        $this->suggestions = null;
        $this->extractionError = null;

        $url = trim((string) ($this->namuWikiForm->getState()['url'] ?? ''));

        try {
            $result = app(ActorInformationExtractor::class)->extract($url);

            $this->suggestions = $result['suggestions'];

            $this->record->candidateSources()->firstOrCreate([
                'kind' => CandidateSource::KIND_NAMUWIKI,
                'url' => $url,
            ]);
        } catch (Throwable $e) {
            $this->extractionError = $e->getMessage();
        }
    }

    public function content(Schema $schema): Schema
    {
        $components = [
            $this->getInfolistContentComponent(),
            Section::make('NamuWiki Actor Information')->schema([
                Form::make([EmbeddedSchema::make('namuWikiForm')])
                    ->id('namu-wiki-form')
                    ->livewireSubmitHandler('extractNamuWiki')
                    ->footer([
                        Actions::make([
                            Action::make('extractNamuWiki')
                                ->label('Extract Actor Information')
                                ->icon('heroicon-o-arrow-down-tray')
                                ->submit('extractNamuWiki'),
                        ]),
                    ]),
            ]),
        ];

        $components[] = Callout::make('NamuWiki extraction failed')
            ->danger()
            ->visible(fn (): bool => $this->extractionError !== null)
            ->description(fn (): ?string => $this->extractionError);

        $components[] = Section::make('Suggested Actor Candidate Information')
            ->visible(fn (): bool => $this->suggestions !== null)
            ->schema(function (): array {
                if ($this->suggestions === null) {
                    return [];
                }

                return [
                    Text::make('Activity Name: '.($this->suggestions['activity_name'] ?? 'None')),
                    Text::make('Name: '.($this->suggestions['name'] ?? 'None')),
                    Text::make('Related Link: '.($this->suggestions['related_link'] ?? 'None')),
                    Text::make('Source URL: '.($this->suggestions['source_url'] ?? 'None')),
                    Text::make('These are suggestions only. The candidate values above are unchanged.')
                        ->color('gray'),
                ];
            });

        $components[] = Section::make('Naver Person Information (Manual Source)')
            ->schema([
                Form::make([EmbeddedSchema::make('naverForm')])
                    ->id('naver-form')
                    ->livewireSubmitHandler('saveNaver')
                    ->footer([
                        Actions::make([
                            Action::make('openNaver')
                                ->label('Open Naver Source')
                                ->icon('heroicon-o-arrow-top-right-on-square')
                                ->url(fn (): ?string => $this->naverUrl())
                                ->openUrlInNewTab()
                                ->visible(fn (): bool => $this->naverUrl() !== null),
                            Action::make('saveNaver')
                                ->label('Save Candidate')
                                ->icon('heroicon-o-check')
                                ->submit('saveNaver'),
                        ]),
                    ]),
            ]);

        return $schema->components($components);
    }
}

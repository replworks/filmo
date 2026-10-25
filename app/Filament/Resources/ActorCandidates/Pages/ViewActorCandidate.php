<?php

namespace App\Filament\Resources\ActorCandidates\Pages;

use App\Filament\Resources\ActorCandidates\ActorCandidateResource;
use App\Models\CandidateSource;
use App\Services\NamuWiki\ActorInformationExtractor;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
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

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->namuWikiForm->fill([
            'url' => $this->record->candidateSources()
                ->where('kind', CandidateSource::KIND_NAMUWIKI)
                ->latest('id')
                ->value('url'),
        ]);
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

        return $schema->components($components);
    }
}

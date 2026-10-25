<?php

namespace Tests\Support;

use App\Filament\Pages\ChannelImport;
use Cable8mm\Youtube\Imports\YoutubeChannelsImport;

class TestableChannelImport extends ChannelImport
{
    public ?YoutubeChannelsImport $importOverride = null;

    public function importForTest(string $path, string $extension): void
    {
        $this->importSpreadsheet($path, $extension);
    }

    protected function makeImport(): YoutubeChannelsImport
    {
        return $this->importOverride ?? parent::makeImport();
    }
}

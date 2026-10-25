<?php

namespace Tests\Support;

use Cable8mm\Youtube\Imports\YoutubeChannelsImport;
use Maatwebsite\Excel\Row;
use RuntimeException;

class FailingYoutubeChannelsImport extends YoutubeChannelsImport
{
    public function onRow(Row $row): void
    {
        if ($row->getIndex() < 2) {
            return;
        }

        $this->results[] = [
            'row' => $row->getIndex(),
            'input' => 'first processed row',
            'result' => 'created',
        ];

        throw new RuntimeException('The file is broken.');
    }
}

<?php

declare(strict_types=1);

/**
 * Fail when line coverage in a Clover report is below the required percentage.
 *
 * Usage: php bin/coverage-threshold.php build/coverage.xml 50
 */
$path = $argv[1] ?? 'build/coverage.xml';
$minimum = (float) ($argv[2] ?? 50);

if (! is_file($path)) {
    fwrite(STDERR, "Coverage report not found: {$path}\n");
    exit(1);
}

$xml = simplexml_load_file($path);

if ($xml === false || ! isset($xml->project->metrics)) {
    fwrite(STDERR, "Coverage report is not a valid Clover file: {$path}\n");
    exit(1);
}

$metrics = $xml->project->metrics;
$statements = (int) $metrics['statements'];
$covered = (int) $metrics['coveredstatements'];

$percentage = $statements === 0 ? 0.0 : ($covered / $statements) * 100;

printf("Line coverage: %.2f%% (%d/%d), required: %.2f%%\n", $percentage, $covered, $statements, $minimum);

exit($percentage >= $minimum ? 0 : 1);

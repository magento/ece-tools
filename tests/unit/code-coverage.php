<?php
/**
 * @author Marco Pivetta
 * @link https://ocramius.github.io/blog/automated-code-coverage-check-for-github-pull-requests-with-travis/
 */
const MIN_COVERAGE = 96;

$inputFile = $argv[1];
$percentage = min(100, max(0, MIN_COVERAGE));

if (!file_exists($inputFile)) {
    throw new InvalidArgumentException('Invalid input file provided');
}

if (!$percentage) {
    throw new InvalidArgumentException('An integer checked percentage must be given as second parameter');
}

$xml = new SimpleXMLElement(file_get_contents($inputFile));
$metrics = $xml->xpath('//metrics');
$totalElements = 0;
$checkedElements = 0;

foreach ($metrics as $metric) {
    $totalElements += (int)$metric['elements'];
    $checkedElements += (int)$metric['coveredelements'];
}

$coverage = round(($checkedElements / $totalElements) * 100, 2);

if ($coverage < $percentage) {
    echo 'Code coverage is ' . $coverage . '%, which is below the accepted ' . $percentage . '%' . PHP_EOL;
    exit(1);
}

echo 'Code coverage is ' . $coverage . '% of ' . $percentage . '% - OK!' . PHP_EOL;

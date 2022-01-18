<?php

require __DIR__ . '/../vendor/autoload.php';

use DataCollector\DatabaseAdapter;
use Symfony\Component\Dotenv\Dotenv;
use DataCollector\EntsoE\Generation;
use DataCollector\EntsoE\Load;

// Parse .env file with configuration
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/../.env');


// Extend Generation data
$firstFetchedDate = (new DatabaseAdapter)->getDb()
    ->query("SELECT `date` FROM `generation` ORDER BY `date` ASC LIMIT 1")
    ->fetch_all()[0][0];
if (strtotime($firstFetchedDate) > strtotime('2012-01-01')) {
    $startDate = (new DateTime($firstFetchedDate))->modify('-6 days');
    $endDate = new DateTime($firstFetchedDate);
    $generation = new Generation;
    $currentDate = new DateTime($startDate->format('Y-m-d'));
    while ($currentDate->format('Y-m-d') !== $endDate->format('Y-m-d')) {
        $generation->actualGenerationPerType(DateTimeImmutable::createFromMutable($currentDate));
        $currentDate->modify('+1 day');
    }
}
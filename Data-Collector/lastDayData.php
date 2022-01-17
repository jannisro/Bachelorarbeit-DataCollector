<?php

require __DIR__ . '/vendor/autoload.php';

use DataCollector\DatabaseAdapter;
use Symfony\Component\Dotenv\Dotenv;
use DataCollector\EntsoE\Generation;

// Parse .env file with configuration
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/.env');


// Determine search period
$startDate = (new DateTime())->modify('-1 day');
$endDate = new DateTime();


// Instantiate EntsoE classes
$generation = new Generation;


// Collect data between dates
$currentDate = new DateTime($startDate->format('Y-m-d'));
while ($currentDate->format('Y-m-d') !== $endDate->format('Y-m-d')) {
    $generation->actualGenerationPerType(DateTimeImmutable::createFromMutable($currentDate));

    $currentDate->modify('+1 day');
    sleep(5); # Wait 5 seconds to prevent overload of API requests
}
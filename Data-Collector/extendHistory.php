<?php

require __DIR__ . '/vendor/autoload.php';

use DataCollector\DatabaseAdapter;
use Symfony\Component\Dotenv\Dotenv;
use DataCollector\EntsoE\Generation;

// Parse .env file with configuration
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/.env');


// Determine search period
$firstFetchedDate = (new DatabaseAdapter)->getDb()
    ->query("SELECT `date` FROM `generation` ORDER BY `date` ASC LIMIT 1")
    ->fetch_all()[0][0];
if (strtotime($firstFetchedDate) > strtotime('2012-01-01')) {
    $startDate = (new DateTime($firstFetchedDate))->modify('-3 days');
    $endDate = new DateTime($firstFetchedDate);

    // Instantiate EntsoE classes
    $generation = new Generation;
    
    
    // Collect data between dates
    $currentDate = new DateTime($startDate->format('Y-m-d'));
    while ($currentDate->format('Y-m-d') !== $endDate->format('Y-m-d')) {
        $generation->actualGenerationPerType(DateTimeImmutable::createFromMutable($currentDate));
    
        $currentDate->modify('+1 day');
        sleep(5); # Wait 5 seconds to prevent overload of API requests
    }
}

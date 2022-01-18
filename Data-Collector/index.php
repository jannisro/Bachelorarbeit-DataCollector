<?php

require __DIR__ . '/vendor/autoload.php';

use DataCollector\DatabaseAdapter;
use Symfony\Component\Dotenv\Dotenv;
use DataCollector\EntsoE\Generation;
use DataCollector\EntsoE\Load;

// Parse .env file with configuration
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/.env');


// Determine search period
// Search manually between dates
if (isset($_GET['startDate'], $_GET['endDate'])) {
    $startDate = new DateTime($_GET['startDate']);
    $endDate = new DateTime($_GET['endDate']);
}
elseif (isset($_GET['history'])) {
    $firstFetchedDate = (new DatabaseAdapter)->getDb()
        ->query("SELECT `date` FROM `generation` ORDER BY `date` ASC LIMIT 1")
        ->fetch_all()[0][0];
    if (strtotime($firstFetchedDate) > strtotime('2012-01-01')) {
        $startDate = (new DateTime($firstFetchedDate))->modify('-3 days');
        $endDate = new DateTime($firstFetchedDate);
    }
}
// Search from last fetched item to today
else {
    $startDate = (new DateTime(
        (new DatabaseAdapter)->getDb()
            ->query("SELECT `date` FROM `generation` ORDER BY `date` DESC LIMIT 1")
            ->fetch_all()[0][0]
    ))->modify('+1 day');
    $endDate = new DateTime();
}


// Instantiate EntsoE classes
$generation = new Generation;
$load = new Load;


// Collect data between dates
$currentDate = new DateTime($startDate->format('Y-m-d'));
while ($currentDate->format('Y-m-d') !== $endDate->format('Y-m-d')) {
    $generation->actualGenerationPerType(DateTimeImmutable::createFromMutable($currentDate));
    $load->actualLoad(DateTimeImmutable::createFromMutable($currentDate));

    $currentDate->modify('+1 day');
    sleep(5); # Wait 5 seconds to prevent overload of API requests
}
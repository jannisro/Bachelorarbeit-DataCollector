<?php

require __DIR__ . '/../../vendor/autoload.php';

use DataCollector\Energy\ElectricityPrice;
use DataCollector\Energy\ResultStoreHelper;
use Symfony\Component\Dotenv\Dotenv;


// Parse .env file with configuration
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/../../.env');


$classes = [
    "ElectricityPrice",
    "CommercialFlow",
    "PhysicalFlow",
    "Generation",
    "Load",
    "ForecastedLoad",
    "NetTransferCapacity"
];

$date = new \DateTimeImmutable('-1 day');
$resultStoreHelper = new ResultStoreHelper;

foreach ($classes as $class) {
    $call = "DataCollector\\Energy\\$class";
    (new $call)($date, $resultStoreHelper);
    echo "<p>$call inserted</p>";
    sleep(10);
}

$resultStoreHelper->storeValues();
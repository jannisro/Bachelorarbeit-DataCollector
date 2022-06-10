<?php

require __DIR__ . '/../../vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;


// Parse .env file with configuration
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/../../.env');


$classes = [
    "Generation",
    "Load",
    "ForecastedLoad"
];

$date = new \DateTimeImmutable('-1 day');

foreach ($classes as $class) {
    $call = "DataCollector\\Energy\\$class";
    (new $call)($date);
    echo "$call inserted... \n";
    sleep(5);
}
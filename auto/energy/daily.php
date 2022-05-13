<?php

require __DIR__ . '/../../vendor/autoload.php';


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
    "NetPosition",
    "NetTransferCapacity"
];

$date = new \DateTimeImmutable('-1 day');

foreach ($classes as $class) {
    (new $class)($date);
    sleep(10);
}
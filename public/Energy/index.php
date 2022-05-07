<?php

require __DIR__ . '/../../vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;


// Parse .env file with configuration
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/../../.env');


$availableClassnames = [
    'CommercialFlow',
    'ElectricityPrice',
    'ForecastedLoad',
    'Generation',
    'InstalledCapacity',
    'Load',
    'NetPosition',
    'NetTransferCapacity',
    'PhysicalFlow'
];


if (isset($_GET['secret']) && in_array($_GET['secret'], $_ENV['APP_SECRET'])) {
    if (isset($_GET['class']) && in_array($_GET['class'], $availableClassnames)) {
        if (isset($_GET['date'])) {
            $date = new \DateTimeImmutable($_GET['date']);
        }
        else {
            $date = new DateTimeImmutable();
        }
    
        $class = "DataCollector\\Energy\\{$_GET['class']}";
        (new $class)($date, false);
    }
    else {
        echo "<h1>Invalid classname</h1>";
    }
}

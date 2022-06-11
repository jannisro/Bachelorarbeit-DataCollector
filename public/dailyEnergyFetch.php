<?php

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

ini_set('display_errors', 1); 
ini_set('display_startup_errors', 1); 
error_reporting(E_ALL);


// Parse .env file with configuration
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/../.env');


$classes = [
    "ElectricityPrice",
    "CommercialFlow",
    "PhysicalFlow",
    "Generation",
    "Load",
    "ForecastedLoad",
    "NetTransferCapacity"
];


if (isset($_GET['secret'], $_GET['date']) && $_GET['secret'] == $_ENV['APP_SECRET']) {
    $date = new \DateTimeImmutable($_GET['date']);

    foreach ($classes as $class) {
        $call = "DataCollector\\Energy\\$class";
        (new $call)($date);
        echo "$call inserted... <br/>";
        sleep(15);
    }
    
    (new DataCollector\Energy\NetPosition)($date);
    echo 'Net Position inserted...';

}
else {
    echo '<p>Valid parameters and secret must be passed</p>';
}
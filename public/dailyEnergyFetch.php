<?php

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;


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
    "NetPosition",
    "NetTransferCapacity"
];


if (isset($_GET['secret'], $_GET['date']) && $_GET['secret'] == $_ENV['APP_SECRET']) {
    $date = new \DateTimeImmutable($_GET['date']);
    foreach ($classes as $class) {
        $call = "DataCollector\\Energy\\$class";
        (new $call)($date);
        echo "<p>$call inserted</p>";
        sleep(10);
    }
}
else {
    echo '<p>Valid parameters and secret must be passed</p>';
}
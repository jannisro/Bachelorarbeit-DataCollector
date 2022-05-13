<?php

require __DIR__ . '/../../vendor/autoload.php';

use DataCollector\Weather\Forecast;
use DataCollector\Weather\RecentHistory;
use Symfony\Component\Dotenv\Dotenv;


// Parse .env file with configuration
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/../../.env');


(new RecentHistory)(new DateTimeImmutable('-1 day'));
sleep(10);
Forecast::flushForecastData();
(new Forecast)();
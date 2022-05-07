<?php

require __DIR__ . '/../../../vendor/autoload.php';

use DataCollector\Energy\ForecastedLoad;
use DataCollector\Energy\Load;
use Symfony\Component\Dotenv\Dotenv;


// Parse .env file with configuration
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/../../../.env');


(new Load)(new DateTimeImmutable('yesterday'));
(new ForecastedLoad)(new DateTimeImmutable('yesterday'));
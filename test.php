<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use DataCollector\Energy\ElectricityPrice;

// Parse .env file with configuration
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/.env');

$c = new ElectricityPrice;
$c->dayAheadPrices(new DateTimeImmutable('2022-04-21'), true);
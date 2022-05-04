<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Parse .env file with configuration
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/../.env');

(new DataCollector\Energy\CommercialFlow)(new DateTimeImmutable('2022-04-26'), true);
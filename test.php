<?php

require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use DataCollector\EntsoE\Outages;

// Parse .env file with configuration
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/.env');

$outages = new Outages;
$outages->load(DateTimeImmutable::createFromMutable((new DateTime())->modify('-2 days')));
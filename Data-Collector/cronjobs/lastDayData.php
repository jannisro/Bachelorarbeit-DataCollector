<?php

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use DataCollector\EntsoE\Generation;
use DataCollector\EntsoE\Load;

// Parse .env file with configuration
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/../.env');

// Instantiate EntsoE classes
$generation = new Generation;
$load = new Load;

// Collect data between dates
$date = (new DateTime())->modify('-1 day');
$generation->actualGenerationPerType(DateTimeImmutable::createFromMutable($date));
$load->actualLoad(DateTimeImmutable::createFromMutable($date));
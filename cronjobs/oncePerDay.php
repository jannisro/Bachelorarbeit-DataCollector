<?php

/**
 * Fetch all data from yesterday
 * This script should be called by a cronjob once a day (in the morning is preferrable)
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use DataCollector\EntsoE\Generation;
use DataCollector\EntsoE\Load;
use DataCollector\EntsoE\Outages;

// Parse .env file with configuration
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/../.env');

// Collect data
$date = (new DateTime())->modify('-1 day');
(new Generation)->actualGenerationPerType(DateTimeImmutable::createFromMutable($date));
(new Load)->actualLoad(DateTimeImmutable::createFromMutable($date));
(new Outages)->load(DateTimeImmutable::createFromMutable($date));
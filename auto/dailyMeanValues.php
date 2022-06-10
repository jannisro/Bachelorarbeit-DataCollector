<?php

require __DIR__ . '/../vendor/autoload.php';

use DataCollector\MeanValueCalculator;
use Symfony\Component\Dotenv\Dotenv;


// Parse .env file with configuration
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/../.env');


(new MeanValueCalculator)(new \DateTimeImmutable('-30 days'), new \DateTimeImmutable());
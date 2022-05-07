<?php

require __DIR__ . '/../../../vendor/autoload.php';

use DataCollector\Energy\Generation;
use Symfony\Component\Dotenv\Dotenv;


// Parse .env file with configuration
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/../../../.env');


(new Generation)(new DateTimeImmutable('yesterday'));
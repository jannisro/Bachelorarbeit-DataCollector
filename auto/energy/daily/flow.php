<?php

require __DIR__ . '/../../../vendor/autoload.php';

use DataCollector\Energy\CommercialFlow;
use DataCollector\Energy\PhysicalFlow;
use Symfony\Component\Dotenv\Dotenv;


// Parse .env file with configuration
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/../../../.env');


(new CommercialFlow)(new DateTimeImmutable('yesterday'));
(new PhysicalFlow)(new DateTimeImmutable('yesterday'));
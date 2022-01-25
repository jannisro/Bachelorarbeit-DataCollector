<?php

require __DIR__ . '/../vendor/autoload.php';


use Symfony\Component\Dotenv\Dotenv;


// Parse .env file with configuration
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/../.env');


include __DIR__ . '/scripts/extendOutageData.php';
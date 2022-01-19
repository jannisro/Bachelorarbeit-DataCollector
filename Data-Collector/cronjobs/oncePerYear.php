<?php

/**
 * Fetch this years capacities
 * This script should be called by a cronjob once a year (January 1st is preferrable)
 */

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use DataCollector\EntsoE\InstalledCapacities;


// Parse .env file with configuration
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/../.env');


$capacities = new InstalledCapacities;

if (isset($_GET['year'])) {
    $capacities->load(new DateTimeImmutable("{$_GET['year']}-07-01"));
}
else {
    $capacities->load(new DateTimeImmutable());
}
<?php

/**
 * Fetch historic data until 2016-01-01
 * This script should be called by a cronjob multiple times daily
 */

use DataCollector\DatabaseAdapter;
use DataCollector\Energy\Outages;

// Extend load data
$firstFetchedDate = (new DatabaseAdapter)->getDb()
    ->query("SELECT `query_date` FROM `outages` ORDER BY `query_date` ASC LIMIT 1")
    ->fetch_all()[0][0];
if (strtotime($firstFetchedDate) > strtotime('2016-01-01')) {
    $startDate = (new DateTime($firstFetchedDate))->modify('-1 day');
    $endDate = new DateTime($firstFetchedDate);
    $load = new Outages;
    $currentDate = new DateTime($startDate->format('Y-m-d'));
    while ($currentDate->format('Y-m-d') !== $endDate->format('Y-m-d')) {
        $load->load(DateTimeImmutable::createFromMutable($currentDate));
        $currentDate->modify('+1 day');
    }
}
<?php

/**
 * Fetch historic data until 2016-01-01
 * This script should be called by a cronjob multiple times daily
 */

use DataCollector\DatabaseAdapter;
use DataCollector\Energy\Generation;

// Extend Generation data
$firstFetchedDate = (new DatabaseAdapter)->getDb()
    ->query("SELECT `date` FROM `generation` ORDER BY `date` ASC LIMIT 1")
    ->fetch_all()[0][0];
if (strtotime($firstFetchedDate) > strtotime('2016-01-01')) {
    $startDate = (new DateTime($firstFetchedDate))->modify('-4 days');
    $endDate = new DateTime($firstFetchedDate);
    $generation = new Generation;
    $currentDate = new DateTime($startDate->format('Y-m-d'));
    while ($currentDate->format('Y-m-d') !== $endDate->format('Y-m-d')) {
        $generation->actualGenerationPerType(DateTimeImmutable::createFromMutable($currentDate));
        $currentDate->modify('+1 day');
    }
}
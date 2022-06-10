<?php

namespace DataCollector\Energy;

use DataCollector\EnergyAdapter;

class NetPosition extends EnergyAdapter
{

    private bool $dryRun;

    /**
     * Calculates and stores net position of all countries
     * @param \DateTimeImmutable $date Date for which data should be queried
     * @param bool $dryRun true=No data is stored and method is run for test purposes
     */
    public function __invoke(\DateTimeImmutable $date, bool $dryRun = false): void
    {
        $this->dryRun = $dryRun;
        foreach (parent::COUNTRIES as $countryKey => $country) {
            $rows = $this->runDbQuery(
                "SELECT * 
                FROM `electricity_history_national` 
                WHERE `country` = '$countryKey' 
                AND `datetime` LIKE '{$date->format('Y-m-d')}%'"
            );
            if ($rows) {
                $this->storeResultInDatabase($rows);
            }
        }
    }


    private function storeResultInDatabase(array $rows): void
    {
        // When TimeSeries is present and dry run is deactivated
        if ($this->dryRun === false) {
            foreach ($rows as $row) {
                $netPos = floatval($row['total_generation']) - floatval($row['load']);
                $this->runDbMultiQuery(
                    "UPDATE `electricity_history_national` 
                    SET `net_position` = '$netPos'
                    WHERE `id` = {$row['id']}"
                );
            }
        }
        else {
            echo "<p>Net Positions would have been inserted into database (DryRun is activated)</p>";
        }
    }

}
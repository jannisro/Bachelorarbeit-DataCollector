<?php

namespace DataCollector\Energy;

use DataCollector\EnergyAdapter;

class NetPosition extends EnergyAdapter
{

    /**
     * Calculates and stores net position of all countries
     * @param \DateTimeImmutable $date Date for which data should be queried
     */
    public function __invoke(\DateTimeImmutable $date): void
    {
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
        foreach ($rows as $row) {
            $netPos = floatval($row['total_generation']) - floatval($row['load']);
            $this->runDbMultiQuery(
                "UPDATE `electricity_history_national` 
                SET `net_position` = '$netPos'
                WHERE `id` = {$row['id']}"
            );
        }
    }

}
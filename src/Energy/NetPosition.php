<?php

namespace DataCollector\Energy;

use DataCollector\EnergyAdapter;

class NetPosition extends EnergyAdapter
{

    private bool $dryRun;

    /**
     * Calculates and stores net positions of all countries
     * @param \DateTimeImmutable $date Date for which data should be queried
     * @param bool $dryRun true=No data is stored and method is run for test purposes
     */
    public function __invoke(\DateTimeImmutable $date, bool $dryRun = false): void
    {
        $this->dryRun = $dryRun;
        foreach (parent::COUNTRIES as $countryKey => $country) {
            $this->calculateAndStoreNetPositions($countryKey, $date);
        }
    }


    private function calculateAndStoreNetPositions(string $country, \DateTimeImmutable $date): void
    {
        $generation = $this->getHourlyGeneration($country, $date);
        $load = $this->getHourlyLoad($country, $date);
        if ($generation && $load && $this->dryRun === false) {
            for ($i = 0; $i < 24; $i++) {
                $this->insertIntoDb('electricity_net_positions', [
                    'country' => $country,
                    'datetime' => "{$date->format('Y-m-d')} $i:00",
                    'value' => floatval($generation[$i]['totalGeneration']) - floatval($load[$i]['value']),
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
        }
        elseif ($this->dryRun) {
            echo "<p>Net Position data of {$date->format('d.m.y')} for country $country would have been inserted</p>";
        }
    }


    private function getHourlyLoad(string $country, \DateTimeImmutable $date): ?array
    {
        return $this->runDbQuery(
            "SELECT `value`
            FROM `electricity_load` 
            WHERE `country`='$country' 
                AND `datetime` LIKE '{$date->format('Y-m-d')}%'
            GROUP BY `datetime`"
        );
    }


    private function getHourlyGeneration(string $country, \DateTimeImmutable $date): ?array
    {
        return $this->runDbQuery(
            "SELECT SUM(`value`) AS `totalGeneration`
            FROM `electricity_generation` 
            WHERE `country`='$country' 
                AND `datetime` LIKE '{$date->format('Y-m-d')}%'
            GROUP BY `datetime`"
        );
    }

}
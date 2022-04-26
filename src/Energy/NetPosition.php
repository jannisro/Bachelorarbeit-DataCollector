<?php

namespace DataCollector\Energy;

use DataCollector\EntsoEAdapter;

class NetPosition extends EntsoEAdapter
{

    private bool $dryRun;

    /**
     * Calculates and stores net positions of all countries
     * @param \DateTimeImmutable $date Date for which data should be queried
     * @param bool $dryRun true=No data is stored and method is run for test purposes
     */
    public function netPositions(\DateTimeImmutable $date, bool $dryRun = false): void
    {
        $this->dryRun = $dryRun;
        foreach (parent::COUNTRIES as $countryKey => $country) {
            if ($this->isDataNotPresent('electricity_net_positions', $countryKey, $date->format('Y-m-d')) || $dryRun) {
                $this->calculateAndStoreNetPositions($countryKey, $date);
            }
        }
        echo 'Done';
    }


    private function calculateAndStoreNetPositions(string $country, \DateTimeImmutable $date): void
    {
        if (
            !$this->isDataNotPresent('electricity_generation', $country, $date->format('Y-m-d'))
            && !$this->isDataNotPresent('electricity_load', $country, $date->format('Y-m-d'))
        ) {
            $generation = $this->getHourlyData('generation', $country, $date);
            $load = $this->getHourlyData('load', $country, $date);
            if ($generation && $load) {
                for ($i = 0; $i < 24; $i++) {
                    $this->insertIntoDb('electricity_net_positions', [
                        'country' => $country,
                        'datetime' => $generation[$i]['datetime'],
                        'value' => floatval($generation[$i]['value']) - floatval($load[$i]['value']),
                        'created_at' => date('Y-m-d H:i')
                    ]);
                }
            }
        }
    }


    private function getHourlyData(string $dataRow, string $country, \DateTimeImmutable $date): ?array
    {
        $table = $dataRow === 'load' ? 'electricity_load' : 'electricity_generation';
        return $this->runDbQuery(
            "SELECT * 
            FROM `$table` 
            WHERE `country`='$country' 
                AND `datetime` LIKE '{$date->format('Y-m-d')}%'"
        );
    }

}
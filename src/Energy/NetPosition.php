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
    public function __invoke(\DateTimeImmutable $date, bool $dryRun = false): void
    {
        $this->dryRun = $dryRun;
        foreach (parent::COUNTRIES as $countryKey => $country) {
            $this->calculateAndStoreNetPositions($countryKey, $date);
        }
        echo 'Done';
    }


    private function calculateAndStoreNetPositions(string $country, \DateTimeImmutable $date): void
    {
        $generation = $this->getHourlyData('generation', $country, $date);
        $load = $this->getHourlyData('load', $country, $date);
        if ($generation && $load && $this->dryRun === false) {
            for ($i = 0; $i < 24; $i++) {
                $this->insertIntoDb('electricity_net_positions', [
                    'country' => $country,
                    'datetime' => $generation[$i]['datetime'],
                    'value' => floatval($generation[$i]['value']) - floatval($load[$i]['value']),
                    'created_at' => date('Y-m-d H:i')
                ]);
            }
        }
        elseif ($this->dryRun) {
            echo "<p>Net Position data of {$date->format('d.m.y')} for country $country would have been inserted</p>";
        }
        else {
            echo "<p>Net Position of {$date->format('d.m.y')} for country $country was not found</p>";
        }
    }


    private function getHourlyData(string $dataRow, string $country, \DateTimeImmutable $date): ?array
    {
        $table = $dataRow === 'load' ? 'electricity_load' : 'electricity_generation';
        print_r("SELECT * 
        FROM `$table` 
        WHERE `country`='$country' 
            AND `datetime` LIKE '{$date->format('Y-m-d')}%'");
        return $this->runDbQuery(
            "SELECT * 
            FROM `$table` 
            WHERE `country`='$country' 
                AND `datetime` LIKE '{$date->format('Y-m-d')}%'"
        );
    }

}
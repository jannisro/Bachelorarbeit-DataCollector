<?php

namespace DataCollector;

class MeanValueCalculator extends EnergyAdapter
{

    public function __invoke(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate): void
    {
        foreach (parent::COUNTRIES as $countryKey => $country) {
            $this->calculateNationalEnergyMeans($countryKey, $startDate, $endDate);
            $this->calculateInternationalEnergyMeans($countryKey, $startDate, $endDate);
            $this->calculateWeatherMeans($countryKey, $startDate, $endDate);
        }
    }


    private function calculateNationalEnergyMeans(string $countryKey, \DateTimeImmutable $startDate, \DateTimeImmutable $endDate): void
    {
        $rows = $this->runDbQuery(
            "SELECT * 
            FROM `electricity_history_national`
            WHERE `country` = '$countryKey'
            AND `datetime` >= '{$startDate->format('Y-m-d 00:00')}'
            AND `datetime` < '{$endDate->format('Y-m-d 24:00')}'"
        );
        if ($rows && count($rows) > 0) {
            $netPos = $price = $generation = $load = 0;
            foreach ($rows as $row) {
                $netPos += floatval($row['net_position']);
                $price += floatval($row['price']);
                $generation += floatval($row['total_generation']);
                $load += floatval($row['load']);
            }
            $this->storeResult('electricity_net_position', $countryKey, $netPos / count($rows));
            $this->storeResult('electricity_price', $countryKey, $price / count($rows));
            $this->storeResult('electricity_generation', $countryKey, $generation / count($rows));
            $this->storeResult('electricity_load', $countryKey, $load / count($rows));
        }
    }


    private function calculateInternationalEnergyMeans(string $countryKey, \DateTimeImmutable $startDate, \DateTimeImmutable $endDate): void
    {
        $outgoing = $this->runDbQuery(
            "SELECT * 
            FROM `electricity_history_international`
            WHERE `country_start` = '$countryKey'
            AND `datetime` >= '{$startDate->format('Y-m-d 00:00')}'
            AND `datetime` < '{$endDate->format('Y-m-d 24:00')}'"
        );
        $incoming = $this->runDbQuery(
            "SELECT * 
            FROM `electricity_history_international`
            WHERE `country_end` = '$countryKey'
            AND `datetime` >= '{$startDate->format('Y-m-d 00:00')}'
            AND `datetime` < '{$endDate->format('Y-m-d 24:00')}'"
        );
        if ($incoming && count($incoming) > 0 && $outgoing && count($outgoing) > 0) {
            $netPos = $price = $generation = $load = 0;
            foreach ($outgoing as $row) {
                $netPos += floatval($row['net_position']);
                $price += floatval($row['price']);
                $generation += floatval($row['total_generation']);
                $load += floatval($row['load']);
            }
            foreach ($incoming as $row) {
                $netPos -= floatval($row['net_position']);
                $price -= floatval($row['price']);
                $generation -= floatval($row['total_generation']);
                $load -= floatval($row['load']);
            }
            $this->storeResult('electricity_net_position', $countryKey, $netPos / count($outgoing));
            $this->storeResult('electricity_price', $countryKey, $price / count($outgoing));
            $this->storeResult('electricity_generation', $countryKey, $generation / count($outgoing));
            $this->storeResult('electricity_load', $countryKey, $load / count($outgoing));
        }
    }


    private function calculateWeatherMeans(string $countryKey, \DateTimeImmutable $startDate, \DateTimeImmutable $endDate): void
    {
        $rows = $this->runDbQuery(
            "SELECT * 
            FROM `weather_points_history`
            INNER JOIN `weather_stations` stations ON `station_id` = stations.`id`
            WHERE stations.`country` = '$countryKey'
            AND `datetime` >= '{$startDate->format('Y-m-d 00:00')}'
            AND `datetime` < '{$endDate->format('Y-m-d 24:00')}'"
        );
        if ($rows && count($rows) > 0) {
            $temperature = $wind = $clouds = $rain = $snow = 0;
            foreach ($rows as $row) {
                $temperature += floatval($row['temperature']);
                $wind += floatval($row['wind']);
                $clouds += floatval($row['clouds']);
                $rain += floatval($row['rain']);
                $snow += floatval($row['snow']);
            }
            $this->storeResult('weather_temperature', $countryKey, $temperature / count($rows));
            $this->storeResult('weather_wind', $countryKey, $wind / count($rows));
            $this->storeResult('weather_clouds', $countryKey, $clouds / count($rows));
            $this->storeResult('weather_rain', $countryKey, $rain / count($rows));
            $this->storeResult('weather_snow', $countryKey, $snow / count($rows));
        }
    }


    private function storeResult(string $fieldName, string $countryKey, float $mean): void
    {
        $dt = date('Y-m-d H:i:s');
        $q = $this->runDbMultiQuery(
            "INSERT INTO `mean_values`
            (`id`, `name`, `country`, `value`, `created_at`, `updated_at`)
            VALUES ('', '$fieldName', '$countryKey', '$mean', '$dt', '$dt')
            ON DUPLICATE KEY UPDATE `value`='$mean', `updated_at`='$dt'"
        );
    }

}
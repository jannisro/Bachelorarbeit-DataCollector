<?php

namespace DataCollector\Weather;

use DataCollector\DatabaseAdapter;

class Forecast extends \DataCollector\WeatherAdapter
{

    private bool $dryRun;


    public static function flushForecastData(): void
    {
        $db = new DatabaseAdapter;
        $db->getDb()->multi_query(
            "TRUNCATE bachelorarbeit.`weather_points_forecast`; 
            TRUNCATE bachelorarbeit.`weather_daylight_hours_forecast`"
        );
    }


    /**
     * Fetches all weather data from the OpenWeatherMap One Call API
     * 
     */
    public function __invoke(int|null $stationLimit = null, int|null $stationOffset = null, bool $dryRun = false): void
    {
        $this->dryRun = $dryRun;
        foreach ($this->getAllStations($stationLimit, $stationOffset) as $station) {
            echo $this->stationData($station) 
                ? "<p style=\"color: #4CAF50\">Successfully retrieved station #{$station['id']} ({$station['country']})</p>" 
                : "<p style=\"color: #F44336\">Failed to retrieve station #{$station['id']} ({$station['country']})</p>";
        }
        echo '<p style="color: #4CAF50">Done</p>';
    }


    private function stationData(array $station): bool
    {
        return $this->processAndStoreResponse(
            $this->forecastRequest($station['lat'], $station['lng']), 
            $station
        );
    }


    private function processAndStoreResponse(object|null $res, array $station): bool
    {
        if ($res && property_exists($res, 'hourly') && property_exists($res, 'current')) {
            return $this->storeData($res, $station);
        }
        else {
            echo '<p style="color: #F44336">Invalid response received</p>';
            var_dump($res);
            return false;
        }
    }


    private function storeData(object $res, array $station): bool
    {
        if ($this->dryRun === false) {
            $this->storeDaylightHours($res->current, $station);
            $this->storeHourlyData($res->hourly, $station);
        }
        else {
            echo "<p>Would have inserted weather for station #{$station['id']} (dry run enabled)</p>";
        }
        return true;
    }


    private function storeDaylightHours(object $currentData, array $station): void
    {
        $this->insertIntoDb('weather_daylight_hours_forecast', [
            'station_id' => $station['id'],
            'country' => $station['country'],
            'date' => date('Y-m-d', intval($currentData->dt)),
            'sunrise' => date('Y-m-d H:i', intval($currentData->sunrise)),
            'sunset' => date('Y-m-d H:i', intval($currentData->sunset)),
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }


    private function storeHourlyData(array $hourlyData, array $station): void
    {
        foreach ($hourlyData as $item) {
            $this->insertIntoDb('weather_points_forecast', [
                'station_id' => $station['id'],
                'country' => $station['country'],
                'datetime' => date('Y-m-d H:i', intval($item->dt)),
                'temperature' => floatval($item->temp),
                'wind' => floatval($item->wind_speed),
                'clouds' => floatval($item->clouds),
                'rain' => isset($item->rain) ? floatval($item->rain->{"1h"}) : 0,
                'snow' => isset($item->snow) ? floatval($item->snow->{"1h"}) : 0,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }
    }

}
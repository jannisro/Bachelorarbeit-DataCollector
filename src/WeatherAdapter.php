<?php

namespace DataCollector;

class WeatherAdapter extends DatabaseAdapter
{

    /**
     * Returns all weather stations of all countries
     * @param int|null $stationLimit Maximum amount of stations returned
     * @param int|null $stationOffset Amounts of stations skipped in result
     */
    public function getAllStations(int|null $stationLimit, int|null $stationOffset): array
    {
        $limitQuery = "";
        if (!is_null($stationLimit)) {
            $limitQuery = "LIMIT {$stationLimit} ";
        }
        if (!is_null($stationOffset)) {
            $limitQuery .= "OFFSET {$stationOffset} ";
        }
        return $this->runDbQuery(
            "SELECT `country`, `lat`, `lng`, `id` 
            FROM `weather_stations` 
            ORDER BY `id` ASC
            $limitQuery"
        );
    }


    /**
     * Fetches hourly data of the last 5 days
     */
    public function historyRequest(\DateTimeImmutable $date, float $lat, float $lng): ?object
    {
        return $this->getApiResponse('/onecall/timemachine', [
            'lat' => $lat,
            'lon' => $lng,
            'dt' => $date->getTimestamp(),
            'units' => 'metric'
        ]);
    }


    /**
     * Fetches hourly data of the last 5 days
     */
    public function forecastRequest(float $lat, float $lng): ?object
    {
        return $this->getApiResponse('/onecall', [
            'lat' => $lat,
            'lon' => $lng,
            'exclude' => 'daily,minutely',
            'units' => 'metric'
        ]);
    }


    /**
     * Makes GET request to OpenWeather API and returns parsed JSON response
     */
    private function getApiResponse (string $endpoint, array $params): ?object
    {
        $url = $_ENV['OPENWEATHER_API_URL'] . $endpoint;
        $url .= "?appid={$_ENV['OPENWEATHER_API_TOKEN']}";
        foreach ($params as $key => $value) {
            $url .= "&$key=$value";
        }
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $json = json_decode(curl_exec($curl));
        curl_close($curl);
        return $json;
    } 

}
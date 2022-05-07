<?php

namespace DataCollector;

class WeatherAdapter extends DatabaseAdapter
{

    /**
     * Returns all weather stations of all countries
     */
    public function getAllStations(): array
    {
        return $this->runDbQuery(
            "SELECT `lat`, `lng`, `id` 
            FROM `weather_stations`"
        );
    }

    /**
     * Fetches hourly data for the next 48h and daily data for the next 7 days
     */
    public function forecast(array $params): ?object
    {
        return $this->getApiResponse('/onecall', $params);
    }


    /**
     * Fetches hourly data of the last 5 days
     */
    public function history(\DateTimeImmutable $date, float $lat, float $lng): ?object
    {
        return $this->getApiResponse('/onecall/timemachine', [
            'lat' => $lat,
            'lng' => $lng,
            'date' => '?'
        ]);
    }


    /**
     * Makes GET request to OpenWeather API and returns parsed JSON response
     */
    private function getApiResponse (string $endpoint, array $params): ?object
    {
        $url = $_ENV['OPENWEATHER_API_URL'] . $endpoint . '?';
        $params['appid'] = $_ENV['OPENWEATHER_API_TOKEN'];
        $url .= implode('&', $params);
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        return json_decode(curl_exec($curl));
    } 

}
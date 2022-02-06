<?php

namespace DataCollector\Weather;

use DataCollector\DatabaseAdapter;

class WeatherAdapter extends DatabaseAdapter
{

    /**
     * Fetches hourly data for the next 48h and daily data for the next 7 days
     * @param array $params Request parameters
     * @return object|null Parsed JSON response or null at error 
     */
    public function getForecast(array $params): ?object
    {
        return $this->getApiResponse('/onecall', $params);
    }


    /**
     * Fetches hourly data of the last 5 days
     * @param array $params Request parameters
     * @return object|null Parsed JSON response or null at error 
     */
    public function getHistory(array $params): ?object
    {
        return $this->getApiResponse('/onecall/timemachine', $params);
    }


    /**
     * Makes GET request to OpenWeather API and returns parsed JSON response
     * @param string $endpoint The endpoint with a leading slash
     * @param array $params All parameters which should be included
     * @return object|null Parsed JSON response or null at error 
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
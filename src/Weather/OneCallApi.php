<?php

namespace DataCollector\Weather;

use DataCollector\WeatherAdapter;

class OneCallApi extends WeatherAdapter
{

    /**
     * Fetches all weather data of a certain date
     */
    public function __invoke(\DateTimeImmutable $date, bool $dryRun = false): void
    {
        foreach ($this->getAllStations() as $station) {
            $this->storeStationData($date, $station);
        }
    }


    private function storeStationData(\DateTimeImmutable $date, array $station): void
    {
        if ($res = $this->history($date, $station['lat'], $station['lng'])) {
            
        }
    }

}
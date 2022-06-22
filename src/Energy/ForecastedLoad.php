<?php

namespace DataCollector\Energy;

use DataCollector\EnergyAdapter;

class ForecastedLoad extends EnergyAdapter
{


    /**
     * Requests and stores elctricity load forecast of all countrie
     * @param \DateTimeImmutable $date Date for which data should be queried
     */
    public function __invoke(\DateTimeImmutable $date): void
    {
        foreach (parent::COUNTRIES as $countryKey => $country) {
            // Fetch data of date
            $response = $this->makeGetRequest([
                'documentType' => 'A65',
                'processType' => 'A01',
                'outBiddingZone_Domain' => $country,
                'periodStart' => \DateTime::createFromImmutable($date)->modify('-1 day')->format('Ymd0000'),
                'periodEnd' => $date->format('Ymd0000')
            ]);
            if (!is_null($response)) {
                $this->storeResultInDatabase($response, $countryKey, $date);
            }
        }
    }


    private function storeResultInDatabase(\SimpleXMLElement $response, string $countryKey, \DateTimeImmutable $date): void
    {
        if ($response->TimeSeries) {
            $time = 0;
            foreach ($this->xmlTimeSeriesToHourlyValues($response, 'quantity') as $hourlyValue) {
                $dt = $date->format('Y-m-d') . " $time:00:00";
                $created = date('Y-m-d H:i:s');
                $this->runDbMultiQuery(
                    "INSERT INTO `electricity_history_national` 
                    (`id`, `datetime`, `country`, `load_forecast`, `created_at`)
                    VALUES ('', '$dt', '$countryKey', '$hourlyValue', '$created')
                    ON DUPLICATE KEY UPDATE `load_forecast`='$hourlyValue'"
                );
                ++$time;
            }
        }
    }

}
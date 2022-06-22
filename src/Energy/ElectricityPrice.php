<?php

namespace DataCollector\Energy;

use DataCollector\EnergyAdapter;

class ElectricityPrice extends EnergyAdapter
{

    /**
     * Requests and stores elctricity prices of all countries (of the first bidding zone in mapping)
     */
    public function __invoke(\DateTimeImmutable $date): void
    {
        foreach ($this->getBiddingZones($date) as $countryKey => $biddingZones) {
            // Fetch data of date
            $response = $this->makeGetRequest([
                'documentType' => 'A44',
                'in_Domain' => $biddingZones[0],
                'out_Domain' => $biddingZones[0],
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
            // Iterate through hourly values and insert them into DB
            $time = 0;
            foreach ($this->xmlTimeSeriesToHourlyValues($response, 'price.amount') as $hourlyValue) {
                $dt = $date->format('Y-m-d') . " $time:00:00";
                $created = date('Y-m-d H:i:s');
                $b = $this->runDbMultiQuery(
                    "INSERT INTO `electricity_history_national` 
                    (`id`, `datetime`, `country`, `price`, `created_at`)
                    VALUES ('', '$dt', '$countryKey', '$hourlyValue', '$created')
                    ON DUPLICATE KEY UPDATE `price`='$hourlyValue'"
                );
                ++$time;
            }
        }
    }

}
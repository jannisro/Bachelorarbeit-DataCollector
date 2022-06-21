<?php

namespace DataCollector\Energy;

use DataCollector\EnergyAdapter;

class PhysicalFlow extends EnergyAdapter
{

    /**
     * Requests and stores physical flows of all border relations
     * @param \DateTimeImmutable $date Date for which data should be queried
     */
    public function __invoke(\DateTimeImmutable $date): void
    {
        foreach (parent::BORDER_RELATIONS as $country => $neighbors) {
            $this->storeDataOfCountry($country, $neighbors, $date);
        }
    }


    private function storeDataOfCountry(string $originCountry, array $neighbors, \DateTimeImmutable $date): void
    {
        foreach ($neighbors as $targetCountry) {
            $this->storeDataOfBorderRelation([$originCountry, $targetCountry], $date);
        }
    }


    private function storeDataOfBorderRelation(array $countries, \DateTimeImmutable $date): void
    {
        $response = $this->makeGetRequest([
            'documentType' => 'A11',
            'out_Domain' => parent::COUNTRIES[$countries[0]],
            'in_Domain' => parent::COUNTRIES[$countries[1]],
            'periodStart' => \DateTime::createFromImmutable($date)->modify('-1 day')->format('Ymd2200'),
            'periodEnd' => $date->format('Ymd2200')
        ]);
        if (!is_null($response)) {
            $this->storeResultInDatabase($response, $countries, $date);
        }
    }


    private function storeResultInDatabase(\SimpleXMLElement $response, array $countries, \DateTimeImmutable $date): void
    {
        if ($response->TimeSeries) {
            // Iterate through hourly values of each PSR and insert them into DB
            $time = 0;
            foreach ($this->xmlTimeSeriesToHourlyValues($response, 'quantity') as $hourlyValue) {
                $dt = $date->format('Y-m-d') . " $time:00:00";
                $created = date('Y-m-d H:i:s');
                $this->runDbMultiQuery(
                    "INSERT INTO `electricity_history_international` 
                    (`id`, `datetime`, `start_country`, `end_country`, `physical_flow`, `created_at`)
                    VALUES ('', '$dt', '{$countries[0]}', '{$countries[1]}', '$hourlyValue', '$created')
                    ON DUPLICATE KEY UPDATE `physical_flow`='$hourlyValue'"
                );
                ++$time;
            }
        }
    }

}
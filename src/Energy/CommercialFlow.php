<?php

namespace DataCollector\Energy;

use DataCollector\EnergyAdapter;

class CommercialFlow extends EnergyAdapter
{

    /**
     * Requests and stores commercial flows of all border relations
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
            'documentType' => 'A09',
            'out_domain' => parent::COUNTRIES[$countries[0]],
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
            $time = 0;
            foreach ($this->xmlTimeSeriesToHourlyValues($response, 'quantity') as $hourlyValue) {
                $dt = $date->format('Y-m-d') . " $time:00:00";
                $this->runDbMultiQuery(
                    "INSERT INTO `electricity_history_international` 
                    (`id`, `datetime`, `start_country`, `end_country`, `commercial_flow`, `created_at`)
                    VALUES ('', '$dt', '{$countries[0]}', '{$countries[1]}', '$hourlyValue', '{date('Y-m-d H:i:s')}')
                    ON DUPLICATE KEY UPDATE `commercial_flow`='$hourlyValue'"
                );
                ++$time;
            }
        }
    }

}
<?php

namespace DataCollector\Energy;

use DataCollector\EnergyAdapter;
use SimpleXMLElement;

class CommercialFlow extends EnergyAdapter
{

    private bool $dryRun;

    /**
     * Requests and stores commercial flows of all border relations
     * @param \DateTimeImmutable $date Date for which data should be queried
     * @param bool $dryRun true=No data is stored and method is run for test purposes
     */
    public function __invoke(\DateTimeImmutable $date, bool $dryRun = false): void
    {
        $this->dryRun = $dryRun;
        foreach (parent::BORDER_RELATIONS as $country => $neighbors) {
            $this->storeDataOfCountry($country, $neighbors, $date);
        }
    }


    private function storeDataOfCountry(string $originCountry, array $neighbors, \DateTimeImmutable $date): void
    {
        foreach ($neighbors as $targetCountry) {
            $this->storeDataOfBorderRelation($originCountry, $targetCountry, $date);
        }
    }


    private function storeDataOfBorderRelation(string $originCountry, string $targetCountry, \DateTimeImmutable $date): void
    {
        $response = $this->makeGetRequest([
            'documentType' => 'A09',
            'out_domain' => parent::COUNTRIES[$originCountry],
            'in_Domain' => parent::COUNTRIES[$targetCountry],
            'periodStart' => \DateTime::createFromImmutable($date)->modify('-1 day')->format('Ymd2200'),
            'periodEnd' => $date->format('Ymd2200')
        ]);
        if (!is_null($response)) {
            $this->storeResultInDatabase($response, $originCountry, $targetCountry, $date);
        }
    }


    private function storeResultInDatabase(\SimpleXMLElement $response, string $country1, string $country2, \DateTimeImmutable $date): void
    {
        // When TimeSeries is present and dry run is deactivated
        if ($response->TimeSeries && $this->dryRun === false) {
            // Iterate through hourly values of each PSR and insert them into DB
            $time = 0;
            foreach ($this->xmlTimeSeriesToHourlyValues($response, 'quantity') as $hourlyValue) {
                $this->insertIntoDb("electricity_flow_commercial", [
                    'country_start' => $country1,
                    'country_end' => $country2,
                    'datetime' => $date->format('Y-m-d') . " $time:00",
                    'value' => $hourlyValue,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                ++$time;
            }
        }
        elseif ($this->dryRun === true) {
            echo "<p>Commercial flow data from " . $date->format('Y-m-d') . " for border '$country1->$country2' would have been inserted into database (DryRun is activated)</p>";
        }
    }

}
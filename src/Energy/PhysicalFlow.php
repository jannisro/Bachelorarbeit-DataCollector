<?php

namespace DataCollector\Energy;

use DataCollector\EnergyAdapter;

class PhysicalFlow extends EnergyAdapter
{

    private bool $dryRun;

    /**
     * Requests and stores physical flows of all border relations
     * @param \DateTimeImmutable $date Date for which data should be queried
     * @param bool $dryRun true=No data is stored and method is run for test purposes
     */
    public function __invoke(\DateTimeImmutable $date, ResultStoreHelper $resultStoreHelper, bool $dryRun = false): ResultStoreHelper
    {
        $this->dryRun = $dryRun;
        foreach (parent::BORDER_RELATIONS as $country => $neighbors) {
            $this->storeDataOfCountry($country, $neighbors, $date,  $resultStoreHelper);
        }
        return $resultStoreHelper;
    }


    private function storeDataOfCountry(string $originCountry, array $neighbors, \DateTimeImmutable $date, ResultStoreHelper $resultStoreHelper): void
    {
        foreach ($neighbors as $targetCountry) {
            $this->storeDataOfBorderRelation([$originCountry, $targetCountry], $date, $resultStoreHelper);
        }
    }


    private function storeDataOfBorderRelation(array $countries, \DateTimeImmutable $date, ResultStoreHelper $resultStoreHelper): void
    {
        $response = $this->makeGetRequest([
            'documentType' => 'A11',
            'out_Domain' => parent::COUNTRIES[$countries[0]],
            'in_Domain' => parent::COUNTRIES[$countries[1]],
            'periodStart' => \DateTime::createFromImmutable($date)->modify('-1 day')->format('Ymd2200'),
            'periodEnd' => $date->format('Ymd2200')
        ]);
        if (!is_null($response)) {
            $this->storeResultInDatabase($response, $countries, $date, $resultStoreHelper);
        }
    }


    private function storeResultInDatabase(\SimpleXMLElement $response, array $countries, \DateTimeImmutable $date, ResultStoreHelper $resultStoreHelper): void
    {
        // When TimeSeries is present and dry run is deactivated
        if ($response->TimeSeries && $this->dryRun === false) {
            // Iterate through hourly values of each PSR and insert them into DB
            $time = 0;
            foreach ($this->xmlTimeSeriesToHourlyValues($response, 'quantity') as $hourlyValue) {
                $resultStoreHelper->addInternationalValue(
                    new \DateTimeImmutable($date->format('Y-m-d') . " $time:00"), 
                    $countries,
                    ['physical_flow', $hourlyValue]
                );
                ++$time;
            }
        }
        elseif ($this->dryRun === true) {
            echo "<p>physical flow data from " . $date->format('Y-m-d') . " for border '{$countries[0]}->{$countries[1]}' would have been inserted into database (DryRun is activated)</p>";
        }
    }

}
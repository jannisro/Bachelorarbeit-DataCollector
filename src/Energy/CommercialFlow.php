<?php

namespace DataCollector\Energy;

use DataCollector\EnergyAdapter;

class CommercialFlow extends EnergyAdapter
{

    private bool $dryRun;

    /**
     * Requests and stores commercial flows of all border relations
     */
    public function __invoke(\DateTimeImmutable $date, ResultStoreHelper $resultStoreHelper, bool $dryRun = false): ResultStoreHelper
    {
        $this->dryRun = $dryRun;
        foreach (parent::BORDER_RELATIONS as $country => $neighbors) {
            $this->storeDataOfCountry($country, $neighbors, $date, $resultStoreHelper);
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
            'documentType' => 'A09',
            'out_domain' => parent::COUNTRIES[$countries[0]],
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
            $time = 0;
            foreach ($this->xmlTimeSeriesToHourlyValues($response, 'quantity') as $hourlyValue) {
                $resultStoreHelper->addInternationalValue(
                    new \DateTimeImmutable($date->format('Y-m-d') . " $time:00"), 
                    $countries, 
                    ['commercial_flow', $hourlyValue]
                );
                ++$time;
            }
        }
        elseif ($this->dryRun === true) {
            echo "<p>Commercial flow data from " . $date->format('Y-m-d') . " for border '{$countries[0]}->{$countries[1]}' would have been inserted into database (DryRun is activated)</p>";
        }
    }

}
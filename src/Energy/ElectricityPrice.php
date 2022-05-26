<?php

namespace DataCollector\Energy;

use DataCollector\EnergyAdapter;

class ElectricityPrice extends EnergyAdapter
{

    private bool $dryRun;

    /**
     * Requests and stores elctricity prices of all countries (of the first bidding zone in mapping)
     */
    public function __invoke(\DateTimeImmutable $date, ResultStoreHelper $resultStoreHelper, bool $dryRun = false): ResultStoreHelper
    {
        $this->dryRun = $dryRun;
        foreach ($this->getBiddingZones($date) as $countryKey => $biddingZones) {
            // Fetch data of date
            $response = $this->makeGetRequest([
                'documentType' => 'A44',
                'in_Domain' => $biddingZones[0],
                'out_Domain' => $biddingZones[0],
                'periodStart' => \DateTime::createFromImmutable($date)->modify('-1 day')->format('Ymd2200'),
                'periodEnd' => $date->format('Ymd2200')
            ]);
            if (!is_null($response)) {
                $this->storeResultInDatabase($response, $countryKey, $date, $resultStoreHelper);
            }
        }
        return $resultStoreHelper;
    }


    private function storeResultInDatabase(\SimpleXMLElement $response, string $countryKey, \DateTimeImmutable $date, ResultStoreHelper $resultStoreHelper): void
    {
        // When TimeSeries is present and dry run is deactivated
        if ($response->TimeSeries && $this->dryRun === false) {
            // Iterate through hourly values and insert them into DB
            $time = 0;
            foreach ($this->xmlTimeSeriesToHourlyValues($response, 'price.amount') as $hourlyValue) {
                $resultStoreHelper->addNationalValue(
                    new \DateTimeImmutable($date->format('Y-m-d') . " $time:00"), 
                    $countryKey, ['price', 
                    $hourlyValue]
                );
                ++$time;
            }
        }
        elseif ($this->dryRun === true) {
            echo "<p>Electricity price data from " . $date->format('Y-m-d') . " for country '$countryKey' would have been inserted into database (DryRun is activated)</p>";
        }
    }

}
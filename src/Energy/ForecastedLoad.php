<?php

namespace DataCollector\Energy;

use DataCollector\EnergyAdapter;

class ForecastedLoad extends EnergyAdapter
{

    private bool $dryRun;


    /**
     * Requests and stores elctricity load forecast of all countrie
     * @param \DateTimeImmutable $date Date for which data should be queried
     * @param bool $dryRun true=No data is stored and method is run for test purposes
     */
    public function __invoke(\DateTimeImmutable $date, ResultStoreHelper $resultStoreHelper, bool $dryRun = false): ResultStoreHelper
    {
        $this->dryRun = $dryRun;
        foreach (parent::COUNTRIES as $countryKey => $country) {
            // Fetch data of date
            $response = $this->makeGetRequest([
                'documentType' => 'A65',
                'processType' => 'A01',
                'outBiddingZone_Domain' => $country,
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
            $time = 0;
            foreach ($this->xmlTimeSeriesToHourlyValues($response, 'quantity') as $hourlyValue) {
                $resultStoreHelper->addNationalValue(
                    new \DateTimeImmutable($date->format('Y-m-d') . " $time:00"), 
                    $countryKey, 
                    ['load_forecast', $hourlyValue]
                );
                ++$time;
            }
        }
        elseif ($this->dryRun === true) {
            echo "<p>Forecasted Load data from " . $date->format('Y-m-d') . " for country '$countryKey' would have been inserted into database (DryRun is activated)</p>";
        }
    }

}
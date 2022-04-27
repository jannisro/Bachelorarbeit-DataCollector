<?php

namespace DataCollector\Energy;

use DataCollector\EntsoEAdapter;

class Load extends EntsoEAdapter
{

    private bool $dryRun;

    /**
     * Requests and stores elctricity load of all countrie
     * @param \DateTimeImmutable $date Date for which data should be queried
     * @param bool $dryRun true=No data is stored and method is run for test purposes
     */
    public function __invoke(\DateTimeImmutable $date, bool $dryRun = false): void
    {
        $this->dryRun = $dryRun;
        foreach (parent::COUNTRIES as $countryKey => $country) {
            // Fetch data of date
            $response = $this->makeGetRequest([
                'documentType' => 'A65',
                'processType' => 'A16',
                'outBiddingZone_Domain' => $country,
                'periodStart' => \DateTime::createFromImmutable($date)->modify('-1 day')->format('Ymd2200'),
                'periodEnd' => $date->format('Ymd2200')
            ]);
            if (!is_null($response)) {
                $this->storeResultInDatabase($response, $countryKey, $date);
            }
        }
        echo 'Done';
    }


    private function storeResultInDatabase(\SimpleXMLElement $response, string $countryKey, \DateTimeImmutable $date): void
    {
        // When TimeSeries is present and dry run is deactivated
        if ($response->TimeSeries && $this->dryRun === false) {
            // Iterate through hourly values of each PSR and insert them into DB
            $time = 0;
            foreach ($this->xmlTimeSeriesToHourlyValues($response, 'quantity') as $hourlyValue) {
                $this->insertIntoDb("electricity_load", [
                    'country' => $countryKey,
                    'datetime' => $date->format('Y-m-d') . "$time:00",
                    'value' => $hourlyValue,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                ++$time;
            }
        }
        elseif ($this->dryRun === true) {
            echo "<p>Load data from " . $date->format('Y-m-d') . " for country '$countryKey' would have been inserted into database (DryRun is activated)</p>";
        }
        else {
            echo "<p>Failed to receive load data for country '$countryKey'</p>";
        }
    }

}
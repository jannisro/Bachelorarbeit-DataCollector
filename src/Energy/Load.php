<?php

namespace DataCollector\Energy;

use SimpleXMLElement;

class Load extends EnergyAdapter
{

    private bool $dryRun;


    /**
     * Requests and stores total grid load of all countries on a certain date
     * @param \DateTimeImmutable $date Date for which data should be queried
     * @param bool $dryRun true=No data is stored and method is run for test purposes
     */
    public function actualLoad(\DateTimeImmutable $date, bool $dryRun = false): void
    {
        $this->dryRun = $dryRun;
        foreach (parent::COUNTRIES as $countryKey => $country) {
            if ($this->isDataNotPresent('load', $countryKey, $date->format('Y-m-d'))) {
                // Fetch data from yesterday
                $response = $this->makeGetRequest([
                    'documentType' => 'A65',
                    'processType' => 'A16',
                    'outBiddingZone_Domain' => $country,
                    'periodStart' => \DateTime::createFromImmutable($date)->modify('-1 day')->format('Ymd2300'),
                    'periodEnd' => $date->format('Ymd2300')
                ]);
                if (!is_null($response)) {
                    $this->storeResultInDatabase($response, $countryKey, $date);
                }
            }
        }
        echo 'Done';
    }


    private function storeResultInDatabase(SimpleXMLElement $response, string $countryKey, \DateTimeImmutable $date): void
    {
        // When TimeSeries is present and dry run is deactivated
        if ($response->TimeSeries && $this->dryRun === false) {
            // Sum up all values
            $pointIndex = $loadSum = 0;
            while ($point = $response->TimeSeries->Period->Point[$pointIndex]) {
                $loadSum += intval($point->quantity->__toString());
                $pointIndex++;
            }
            // Write summed load to database
            $this->insertIntoDb('load', [
                'country' => $countryKey,
                'date' => $date->format('Y-m-d'),
                'amount' => $loadSum,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }
        elseif ($this->dryRun === true) {
            echo "<p>Load data from " . $date->format('Y-m-d') . " for country '$countryKey' would have been inserted into database (DryRun is activated)</p>";
        }
    }

}
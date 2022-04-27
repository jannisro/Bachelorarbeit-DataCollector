<?php

namespace DataCollector\Energy;

use DataCollector\EntsoEAdapter;

class NetTransferCapacity extends EntsoEAdapter
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
        foreach (parent::BORDER_RELATIONS as $country1 => $neighbors) {
            $this->storeCountryData($country1, $neighbors, $date);
        }
        echo 'Done';
    }


    /**
     * Stores data of all borders of a countries
     */
    private function storeCountryData(string $originCountry, array $neighbors, \DateTimeImmutable $date): void
    {
        foreach ($neighbors as $targetCountry) {
            $this->storeBorderRelationData(
                $this->getHourlyValuesOfBorderRelation($originCountry, $targetCountry, $date),
                $originCountry,
                $targetCountry,
                $date
            );
        }
    }


    /**
     * Returns hourly values of country->country
     */
    private function getHourlyValuesOfBorderRelation(string $originCountry, string $targetCountry, \DateTimeImmutable $date): array
    {
        $totalHourlyValues = array_fill(0, 24, 0);
        foreach ($this->getBiddingZones($date)[$originCountry] as $originBiddingZone) {
            $totalHourlyValues = $this->addArrayValues(
                $totalHourlyValues, 
                $this->getHourlyValuesOfOriginZone($originBiddingZone, $targetCountry, $date)
            );
        }
        return $totalHourlyValues;
    }


    /**
     * Returns hourly values of BiddingZone->Country
     */
    private function getHourlyValuesOfOriginZone(string $originBiddingZone, string $targetCountry, \DateTimeImmutable $date): array 
    {
        $totalHourlyValues = array_fill(0, 24, 0);
        foreach ($this->getBiddingZones($date)[$targetCountry] as $targetBiddingZone) {
            $totalHourlyValues = $this->addArrayValues(
                $totalHourlyValues,
                $this->getHourlyValuesOfZoneRelation($originBiddingZone, $targetBiddingZone, $date)
            );
        }
        return $totalHourlyValues;
    }


    /**
     * Returns hourly values of BiddingZone->BiddingZone
     */
    private function getHourlyValuesOfZoneRelation(string $originBiddingZone, string $targetBiddingZone, \DateTimeImmutable $date): array
    {
        $response = $this->makeGetRequest([
            'documentType' => 'A61',
            'contract_MarketAgreement.Type' => 'A01',
            'in_Domain' => $targetBiddingZone,
            'out_Domain' => $originBiddingZone,
            'periodStart' => \DateTime::createFromImmutable($date)->modify('-1 day')->format('Ymd2200'),
            'periodEnd' => $date->format('Ymd2200')
        ]);
        if (!is_null($response) && $response->TimeSeries && $this->dryRun === false) {
            return $this->xmlTimeSeriesToHourlyValues($response, 'quantity');
        }
        return array_fill(0, 24, 0);
    }


    /**
     * Stores hourly values of country->country in database
     */
    private function storeBorderRelationData(array $hourlyValues, string $country1, string $country2, \DateTimeImmutable $date): void
    {
        // When TimeSeries is present and dry run is deactivated
        if (array_sum($hourlyValues) > 0 && $this->dryRun === false) {
            $time = 0;
            foreach ($hourlyValues as $hourlyValue) {
                $this->insertIntoDb("electricity_net_transfer_capacities", [
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
            echo "<p>NTC data from " . $date->format('Y-m-d') . " for border '$country1->$country2' would have been inserted into database (DryRun is activated)</p>";
        }
        else {
            echo "<p>Failed to receive NTC data for border '$country1->$country2'</p>";
        }
    }


    private function addArrayValues(array $existingArray, array $arrayToAdd): array 
    {
        for ($i=0; $i < count($arrayToAdd); $i++) { 
            $existingArray[$i] += $arrayToAdd[$i];
        }
        return $existingArray;
    }

}
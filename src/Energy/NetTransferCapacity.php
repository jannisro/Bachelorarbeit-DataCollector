<?php

namespace DataCollector\Energy;

use DataCollector\EnergyAdapter;

class NetTransferCapacity extends EnergyAdapter
{

    /**
     * Requests and stores commercial flows of all border relations
     * @param \DateTimeImmutable $date Date for which data should be queried
     */
    public function __invoke(\DateTimeImmutable $date): void
    {
        foreach (parent::BORDER_RELATIONS as $country1 => $neighbors) {
            $this->storeCountryData($country1, $neighbors, $date);
        }
    }


    /**
     * Stores data of all borders of a countries
     */
    private function storeCountryData(string $originCountry, array $neighbors, \DateTimeImmutable $date): void
    {
        foreach ($neighbors as $targetCountry) {
            $this->storeBorderRelationData(
                $this->getHourlyValuesOfBorderRelation($originCountry, $targetCountry, $date),
                [$originCountry, $targetCountry],
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
            'periodStart' => \DateTime::createFromImmutable($date)->modify('-1 day')->format('Ymd0000'),
            'periodEnd' => $date->format('Ymd0000')
        ]);
        if (!is_null($response) && $response->TimeSeries) {
            return $this->xmlTimeSeriesToHourlyValues($response, 'quantity');
        }
        return array_fill(0, 24, 0);
    }


    /**
     * Stores hourly values of country->country in database
     */
    private function storeBorderRelationData(array $hourlyValues, array $countries, \DateTimeImmutable $date): void
    {
        if (array_sum($hourlyValues) > 0) {
            $time = 0;
            foreach ($hourlyValues as $hourlyValue) {
                $dt = $date->format('Y-m-d') . " $time:00:00";
                $created = date('Y-m-d H:i:s');
                $this->runDbMultiQuery(
                    "INSERT INTO `electricity_history_international` 
                    (`id`, `datetime`, `start_country`, `end_country`, `net_transfer_capacity`, `created_at`)
                    VALUES ('', '$dt', '{$countries[0]}', '{$countries[1]}', '$hourlyValue', '$created')
                    ON DUPLICATE KEY UPDATE `net_transfer_capacity`='$hourlyValue'"
                );
                ++$time;
            }
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
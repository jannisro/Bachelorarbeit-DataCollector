<?php

namespace DataCollector\Energy;

use DataCollector\EnergyAdapter;

class InstalledCapacity extends EnergyAdapter
{

    /**
     * Requests and stores installed capacites of all countries, identified by PSR type
     * @param \DateTimeImmutable $date Date of the year for which data should be queried
     */
    public function __invoke(\DateTimeImmutable $date): void
    {
        foreach (parent::COUNTRIES as $countryKey => $country) {
            $response = $this->makeGetRequest([
                'documentType' => 'A68',
                'processType' => 'A33',
                'in_domain' => $country,
                'periodStart' => \DateTime::createFromImmutable($date)->modify('-1 year')->format('Y12312000'),
                'periodEnd' => $date->format('Y12312000')
            ]);
            if (!is_null($response)) {
                $this->storeResultInDatabase($response, $countryKey, $date);
            }
        }
    }


    private function storeResultInDatabase(\SimpleXMLElement $response, string $countryKey, \DateTimeImmutable $date): void
    {
        if ($response->TimeSeries) {
            // Add all PSR capacity values to the database
            foreach ($this->getAllPsrValues($response) as $psrType => $capacityAmount) {
                $this->insertIntoDb('electricity_installed_capacities', [
                    'country' => $countryKey,
                    'year' => $date->format('Y'),
                    'psr_type' => $psrType,
                    'value' => $capacityAmount,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
        }
    }


    /**
     * Takes XML response from EntsoE and returns an array with the generated sum per PSR type
     * @param \SimpleXMLElement $xml Response from the EntsoE API
     * @return array Associative array of format ['psr_code' => total_capacity_in_mw] 
     */
    private function getAllPsrValues(\SimpleXMLElement $xml): array
    {
        $result = [];
        // Iterate over TimeSeries
        $seriesIndex = 0;
        while ($series = $xml->TimeSeries[$seriesIndex]) {
            $psr = $series->MktPSRType->psrType->__toString();
            $result[$psr] = intval($series->Period->Point->quantity->__toString());
            ++$seriesIndex;
        }
        return $result;
    }

}
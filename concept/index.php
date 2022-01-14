<?php

class ActualGeneration
{

    const API_URL = 'https://transparency.entsoe.eu/api';
    const API_TOKEN = '51e4e08a-09b1-41ea-9da1-39dd72b48e33';
    const BASE_URL = self::API_URL . '?securityToken=' . self::API_TOKEN;
    const COUNTRIES = [
        'AL' => '10YAL-KESH-----5',
        'AT' => '10YAT-APG------L',
        'BA' => '10YBA-JPCC-----D',
        'BE' => '10YBE----------2',
        'BG' => '10YCA-BULGARIA-R',
        'BY' => '10Y1001A1001A51S',
        'CH' => '10YCH-SWISSGRIDZ',
        'CZ' => '10YCZ-CEPS-----N',
        'DE' => '10Y1001A1001A83F',
        'DK' => '10Y1001A1001A65H',
        'EE' => '10Y1001A1001A39I',
        'ES' => '10YES-REE------0',
        'FI' => '10YFI-1--------U',
        'FR' => '10YFR-RTE------C',
        'GB' => '10YGB----------A',
        'GB_NIR' => '10Y1001A1001A016',
        'GR' => '10YGR-HTSO-----Y',
        'HR' => '10YHR-HEP------M',
        'HU' => '10YHU-MAVIR----U',
        'IE' => '10YIE-1001A00010',
        'IT' => '10YIT-GRTN-----B',
        'LT' => '10YLT-1001A0008Q',
        'LU' => '10YLU-CEGEDEL-NQ',
        'LV' => '10YLV-1001A00074',
        'ME' => '10YCS-CG-TSO---S',
        'MK' => '10YMK-MEPSO----8',
        'MT' => '10Y1001A1001A93C',
        'NL' => '10YNL----------L',
        'NO' => '10YNO-0--------C',
        'PL' => '10YPL-AREA-----S',
        'PT' => '10YPT-REN------W',
        'RO' => '10YRO-TEL------P',
        'RS' => '10YCS-SERBIATSOV',
        'RU' => '10Y1001A1001A49F',
        'RU_KGD' => '10Y1001A1001A50U',
        'SE' => '10YSE-1--------K',
        'SI' => '10YSI-ELES-----O',
        'SK' => '10YSK-SEPS-----K',
        'TR' => '10YTR-TEIAS----W',
        'UA' => '10YUA-WEPS-----0'
    ];


    private mysqli $db;


    public function __construct()
    {
        $this->db = new mysqli('sql459.your-server.de', 'jannism_1', 'gTFC9cwHr9UiVKf8', 'bachelorarbeit');
    }


    public function actualGenerationPerType(): void
    {
        foreach (self::COUNTRIES as $countryKey => $country) {
            if ($this->isDataNotPresent($countryKey, date('Y-m-d', strtotime('-1 day')))) {
                // Fetch data from yesterday
                $response = $this->makeGetRequest([
                    'documentType' => 'A75',
                    'processType' => 'A16',
                    'in_domain' => $country,
                    'periodStart' => date('Ymd2300', strtotime('-2 days')),
                    'periodEnd' => date('Ymd2300', strtotime('-1 day'))
                ]);
    
                // When TimeSeries is present (<=> No error occurred and data is available)
                if ($response->TimeSeries) {
                    // Add all PSR generation values to the database
                    foreach ($this->getAllPsrValues($response) as $psrType => $generationAmount) {
                        $this->insertIntoDb('generation', [
                            'country' => $countryKey,
                            'date' => date('Y-m-d', strtotime('-1 day')),
                            'psr_type' => $psrType,
                            'amount' => $generationAmount,
                            'created_at' => date('Y-m-d H:i:s')
                        ]);
                    }
                }
            }
        }
    }


    private function getAllPsrValues(SimpleXMLElement $xml): array
    {
        $result = [];
        // Iterate over TimeSeries
        $seriesIndex = 0;
        while ($series = $xml->TimeSeries[$seriesIndex]) {
            // Create PSR in array if not present
            $psr = $series->MktPSRType->psrType->__toString();
            if (!isset($result[$psr])) $result[$psr] = 0;
            // Iiterate over Points in TimeSeries
            $pointIndex = 0;
            while ($point = $series->Period->Point[$pointIndex]) {
                // Add Generation to sum
                $result[$psr] += intval($point->quantity->__toString());
                $pointIndex++;
            }
            $seriesIndex++;
        }
        return $result;
    }


    private function isDataNotPresent(string $countryKey, string $date): bool
    {
        $res = $this->db->query("SELECT * FROM `generation` WHERE `country` = '$countryKey' AND `date` = '$date'");
        return $res->num_rows === 0;
    }


    private function makeGetRequest(array $params): ?SimpleXMLElement
    {
        $url = self::BASE_URL;
        foreach ($params as $key => $value) {
            $url .= "&$key=$value";
        }
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $xml = simplexml_load_string(curl_exec($curl));
        curl_close($curl);
        return $xml ? $xml : null;
    }


    private function insertIntoDb(string $table, array $data): bool
    {
        $cols = '`' . implode('`, `', array_keys($data)) . '`';
        $values = "'" . implode("', '", array_values($data)) . "'";
        return $this->db->query("INSERT INTO $table ($cols) VALUES ($values)");
    }
    
}

$app = new ActualGeneration();
$app->actualGenerationPerType();
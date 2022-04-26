<?php

namespace DataCollector;

class EntsoeAdapter extends DatabaseAdapter
{

    protected string $apiUrl;

    const COUNTRIES = [
        'AL' => '10YAL-KESH-----5',
        'AT' => '10YAT-APG------L',
        'BA' => '10YBA-JPCC-----D',
        'BE' => '10YBE----------2',
        'BG' => '10YCA-BULGARIA-R',
        'CH' => '10YCH-SWISSGRIDZ',
        'CZ' => '10YCZ-CEPS-----N',
        'DE' => '10Y1001A1001A83F',
        'DK' => '10Y1001A1001A65H',
        'EE' => '10Y1001A1001A39I',
        'ES' => '10YES-REE------0',
        'FI' => '10YFI-1--------U',
        'FR' => '10YFR-RTE------C',
        'GR' => '10YGR-HTSO-----Y',
        'HR' => '10YHR-HEP------M',
        'HU' => '10YHU-MAVIR----U',
        'IT' => '10YIT-GRTN-----B',
        'LT' => '10YLT-1001A0008Q',
        'LU' => '10YLU-CEGEDEL-NQ',
        'LV' => '10YLV-1001A00074',
        'ME' => '10YCS-CG-TSO---S',
        'MK' => '10YMK-MEPSO----8',
        'NL' => '10YNL----------L',
        'NO' => '10YNO-0--------C',
        'PL' => '10YPL-AREA-----S',
        'PT' => '10YPT-REN------W',
        'RO' => '10YRO-TEL------P',
        'RS' => '10YCS-SERBIATSOV',
        'SE' => '10YSE-1--------K',
        'SI' => '10YSI-ELES-----O',
        'SK' => '10YSK-SEPS-----K'
    ];


    const BIDDING_ZONES = [
        'AL' => ['10YAL-KESH-----5'],
        'AT' => ['10YAT-APG------L'],
        'BA' => ['10YBA-JPCC-----D'],
        'BE' => ['10YBE----------2'],
        'BG' => ['10YCA-BULGARIA-R'],
        'CH' => ['10YCH-SWISSGRIDZ'],
        'CZ' => ['10YCZ-CEPS-----N'],
        'DE' => ['10Y1001A1001A82H'],
        'DK' => ['10YDK-1--------W', '10YDK-2--------M'],
        'EE' => ['10Y1001A1001A39I'],
        'ES' => ['10YES-REE------0'],
        'FI' => ['10YFI-1--------U'],
        'FR' => ['10YFR-RTE------C'],
        'GR' => ['10YGR-HTSO-----Y'],
        'HR' => ['10YHR-HEP------M'],
        'HU' => ['10YHU-MAVIR----U'],
        'IT' => ['10YIT-GRTN-----B'],
        'LT' => ['10YLT-1001A0008Q'],
        'LU' => ['10Y1001A1001A82H'],
        'LV' => ['10YLV-1001A00074'],
        'ME' => ['10YCS-CG-TSO---S'],
        'MK' => ['10YMK-MEPSO----8'],
        'NL' => ['10YNL----------L'],
        'NO' => ['10YNO-2--------T', '10YNO-1--------2', '50Y0JVU59B4JWQCU', '10YNO-3--------J', '10YNO-4--------9', '10Y1001A1001A48H'],
        'PL' => ['10YPL-AREA-----S'],
        'PT' => ['10YPT-REN------W'],
        'RO' => ['10YRO-TEL------P'],
        'RS' => ['10YCS-SERBIATSOV'],
        'SE' => ['10Y1001A1001A44P', '10Y1001A1001A45N', '10Y1001A1001A46L', '10Y1001A1001A47J'],
        'SI' => ['10YSI-ELES-----O'],
        'SK' => ['10YSK-SEPS-----K']
    ];

    const BIDDING_ZONES_CHANGES_PRE_2018 = [
        'AT' => ['10Y1001A1001A63L'],
        'DE' => ['10Y1001A1001A63L'],
        'LU' => ['10Y1001A1001A63L'],
    ];


    const BORDER_RELATIONS = [
        'AL' => ['GR', 'ME', 'RS'],
        'AT' => ['CZ', 'DE', 'HU', 'IT', 'SI', 'CH'],
        'BA' => ['HR', 'ME', 'RS'],
        'BE' => ['FR', 'DE', 'NL', 'LU'],
        'BG' => ['GR', 'RO', 'RS'],
        'CH' => ['AT', 'FR', 'DE', 'IT'],
        'CZ' => ['AT', 'DE', 'PL', 'SK'],
        'DE' => ['NL', 'BE', 'FR', 'CH', 'AT', 'CZ', 'PL', 'DK', 'NO', 'SE', 'LU'],
        'DK' => ['DE', 'NL', 'NO', 'SE'],
        'EE' => ['FI', 'LV'],
        'ES' => ['FR', 'PT'],
        'FI' => ['EE', 'NO', 'SE'],
        'FR' => ['BE', 'DE', 'IT', 'ES', 'CH'],
        'GR' => ['AL', 'BG', 'IT'],
        'HR' => ['BA', 'HU', 'RS', 'SI'],
        'HU' => ['AT', 'HR', 'RO', 'RS', 'SK'],
        'IT' => ['AT', 'FR', 'GR', 'ME', 'SI', 'CH'],
        'LT' => ['LV', 'PL', 'SE'],
        'LU' => ['DE', 'BE'],
        'LV' => ['EE', 'LT'],
        'ME' => ['AL', 'IT', 'RS'],
        'MK' => ['BG', 'GR', 'RS'],
        'NL' => ['BE', 'DK', 'DE', 'NO'],
        'NO' => ['DK', 'FI', 'DE', 'NL', 'SE'],
        'PL' => ['CZ', 'DE', 'LT', 'SK', 'SE'],
        'PT' => ['ES'],
        'RO' => ['BG', 'HU', 'RS'],
        'RS' => ['AL', 'BA', 'BG', 'HR', 'HU', 'ME', 'RO', 'MK'],
        'SE' => ['DK', 'FI', 'DE', 'LT', 'NO', 'PL'],
        'SI' => ['AT', 'HR', 'IT'],
        'SK' => ['CZ', 'HU', 'PL']
    ];


    public function __construct()
    {
        parent::__construct();
        $this->apiUrl = "{$_ENV['ENTSOE_API_URL']}?securityToken={$_ENV['ENTSOE_API_TOKEN']}";
    }


    /**
     * Performs GET request to the EntsoE API
     * @param array $params All needed parameters [name=>value]
     * @return \SimpleXMLElement Parsed XML or null at error
     */
    protected function makeGetRequest(array $params): ?\SimpleXMLElement
    {
        $url = $this->apiUrl;
        foreach ($params as $key => $value) {
            $url .= "&$key=$value";
        }
        echo "<p>$url</p>";
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $xml = simplexml_load_string(curl_exec($curl));
        curl_close($curl);
        return $xml ? $xml : null;
    }


    /**
     * Performs GET request to an endpoint with a ZIP file attached to the response
     * @param array $params All needed parameters [name=>value]
     * @return string|null Path to the zip file or null at failure
     */
    /*protected function makeGetRequestWithZipResponse(array $params): ?string
    {
        $url = $this->apiUrl;
        foreach ($params as $key => $value) {
            $url .= "&$key=$value";
        }
        if(!is_dir(__DIR__ . '/../../tmp')) {
            mkdir(__DIR__ . '/../../tmp');
        }
        $fileName = __DIR__ . '/../../tmp/' . mt_rand(100, 999) . time() . '.zip';
        $response = file_get_contents($url);
        // When response is binary (<=> ZIP returned)
        if (!preg_match('//u', $response)) {
            file_put_contents($fileName, $response);
            return $fileName;
        }
        return null;
    }*/


    /**
     * Checks whether the datbase already contains data for a given country and date
     */
    protected function isDataNotPresent(string $tableName, string $countryKey, string $date): bool
    {
        $res = $this->getDb()->query(
            "SELECT * 
            FROM `$tableName` 
            WHERE `country` = '$countryKey' 
                AND `datetime` LIKE '$date%'"
        );
        return $res && $res->num_rows === 0;
    }


    /**
     * Sums up each n elements in an array of 24 items
     */
    protected function aggregateValues(array $values, int $elementsToUnite): array {
        $result = array_fill(0, 24, 0);
        $currentIndexInResult = $currentlyUnitedElements = 0;
        foreach ($values as $value) {
            $result[$currentIndexInResult] += $value;
            if (++$currentlyUnitedElements === $elementsToUnite) {
                ++$currentIndexInResult;
                $currentlyUnitedElements = 0;
            }
        }
        return $result;
    }


    /**
     * Transforms XML time series data to array
     */
    protected function xmlTimeSeriesToArray(\SimpleXMLElement $xml, string $dataElementName): array
    {
        $result = [];
        // Iterate over TimeSeries
        $seriesIndex = 0;
        while ($series = $xml->TimeSeries[$seriesIndex]) {
            // Iiterate over Points in TimeSeries
            $pointIndex = 0;
            while ($point = $series->Period->Point[$pointIndex]) {
                $result[] = floatval($point->{$dataElementName}->__toString());
                $pointIndex++;
            }
            $seriesIndex++;
        }
        return $result;
    }


    /**
     * Transforms raw time series data to hourly aggregated
     */
    protected function aggregateHourlyValues(array $rawValues): array
    {
        $result = [];
        // Raw values are hourly => No need for processing
        if (count($rawValues) === 24) {
            $result = $rawValues;
        }
        // Raw values are quarter hourly => Aggregate to hourly
        else if (count($rawValues) === 96) {
            $result = $this->aggregateValues($rawValues, 4);
        }
        // Raw values are half hourly => Aggregate to hourly
        else if (count($rawValues) === 48) {
            $result = $this->aggregateValues($rawValues, 2);
        }
        // Incomplete dataset => Process anyway to keep existing data
        else if (count($rawValues) < 24) {
            $result = $this->aggregateValues($rawValues, 1);
        }
        return $result;
    }


    /**
     * Transforms XML time series to aggregated hourly array
     */
    protected function xmlTimeSeriesToHourlyValues(\SimpleXMLElement $xml, string $dataElementName): array
    {
        return $this->aggregateHourlyValues(
            $this->xmlTimeSeriesToArray($xml, $dataElementName)
        );
    }

}
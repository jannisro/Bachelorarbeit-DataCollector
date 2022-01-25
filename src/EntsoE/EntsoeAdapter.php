<?php

namespace DataCollector\EntsoE;

use DataCollector\DatabaseAdapter;

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


    const OUTAGE_ZONES = [
        'DE' => '10Y1001A1001A82H',
        'FR' => '10YFR-RTE------C',
        'AT' => '10YAT-APG------L',
        'BE' => '10YBE----------2',
        'BG' => '10YCA-BULGARIA-R',
        'CH' => '10YCH-SWISSGRIDZ',
        'CZ' => '10YCZ-CEPS-----N',
        'ES' => '10YES-REE------0',
        'HU' => '10YHU-MAVIR----U',
        'IT' => '10YIT-GRTN-----B',
        'NL' => '10YNL----------L',
        'NO' => '10YNO-0--------C',
        'PL' => '10YPL-AREA-----S',
        'PT' => '10YPT-REN------W',
        'RO' => '10YRO-TEL------P',
        'SE' => '10YSE-1--------K',
        'DK_1' => '10YDK-1--------W',
        'DK_2' => '10YDK-2--------M'
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
    protected function makeGetRequestWithZipResponse(array $params): ?string
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
    }


    /**
     * Checks whether the datbase already contains data for a given country and date
     */
    protected function isDataNotPresent(string $tableName, string $countryKey, string $date): bool
    {
        $res = $this->getDb()->query("SELECT * FROM `$tableName` WHERE `country` = '$countryKey' AND `date` = '$date'");
        return $res && $res->num_rows === 0;
    }

}
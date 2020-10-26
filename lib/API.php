<?php

namespace WHMCS\Module\Server\lip_rhipe_csp;

class API {

    private $clientid;
    private $secretkey;
    
    public function __construct($clientid, $secretkey)
    {
        $this->clientid = $clientid;
        $this->secretkey = $secretkey;
    }

    public function getAzureUsage($subscriptionId) {
        $start = date('Y-m-01') . 'T00:00:00.000Z';
        $end = date("Y-m-01", strtotime('+1 month')) . 'T00:00:00.000Z';
        $queryString = http_build_query([
            "startDate" => $start,
            "endDate" => $end
        ]);


        $token = $this->_getToken();
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.prismportal.online/api/v2/microsoftcsp/azure/usage/summary/{$subscriptionId}?{$queryString}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer {$token}",
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return json_decode($response);
    }

    public function getTenantsAndSubscriptions($contractAgreementId) {

        $token = $this->_getToken();

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.prismportal.online/api/v2/contractagreements/{$contractAgreementId}/tenants",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer {$token}",
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return json_decode($response);
    }

    private function _getToken() {

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://identity.prismportal.online/core/connect/token",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => http_build_query([
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientid,
                'client_secret' => $this->secretkey,
                'scope' => 'rhipeapi',
            ]),
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/x-www-form-urlencoded",
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return json_decode($response)->access_token;
    }

}
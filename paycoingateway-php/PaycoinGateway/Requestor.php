<?php

class PaycoinGateway_Requestor
{

    public function doCurlRequest($curl)
    {
        $response = curl_exec($curl);

        // Check for errors
        if($response === false) {
            $error = curl_errno($curl);
            $message = curl_error($curl);
            curl_close($curl);
            throw new PaycoinGateway_ConnectionException("Network error " . $message . " (" . $error . ")");
        }

        // Check status code
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $url = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);
        curl_close($curl);
        if($statusCode != 200) {
            throw new PaycoinGateway_ApiException("Status code " . $statusCode . " URL ".$url, $statusCode, $response);
        }

        return array( "statusCode" => $statusCode, "body" => $response );
    }

}
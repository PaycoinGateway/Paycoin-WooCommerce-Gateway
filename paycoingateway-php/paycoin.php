<?php

if(!function_exists('curl_init')) {
    throw new Exception('The PaycoinGateway client library requires the CURL PHP extension.');
}

require_once(dirname(__FILE__) . '/PaycoinGateway/Exception.php');
require_once(dirname(__FILE__) . '/PaycoinGateway/ApiException.php');
require_once(dirname(__FILE__) . '/PaycoinGateway/ConnectionException.php');
require_once(dirname(__FILE__) . '/PaycoinGateway/PaycoinGateway.php');
require_once(dirname(__FILE__) . '/PaycoinGateway/Requestor.php');
require_once(dirname(__FILE__) . '/PaycoinGateway/Rpc.php');
require_once(dirname(__FILE__) . '/PaycoinGateway/OAuth.php');
require_once(dirname(__FILE__) . '/PaycoinGateway/TokensExpiredException.php');
require_once(dirname(__FILE__) . '/PaycoinGateway/Authentication.php');
require_once(dirname(__FILE__) . '/PaycoinGateway/SimpleApiKeyAuthentication.php');
require_once(dirname(__FILE__) . '/PaycoinGateway/OAuthAuthentication.php');
require_once(dirname(__FILE__) . '/PaycoinGateway/ApiKeyAuthentication.php');

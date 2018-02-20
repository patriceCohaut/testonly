<?php
/******************************************************************************
 * GENERIC API client PHP for accessing APIs of third parties 
 /******************************************************************************
 * OmniPCX Open Gateway 
 * @category   SAMPLE CODE
 * @package    O2G
 * @author     Patrice COHAUT <patrice.cohaut@al-enterprise.com>
 * @copyright  2018  ALE
 * @version    0.9.9
 ******************************************************************************/

define("ENABLE_DEBUG_INFO", true); 

/**
 * Different AUTH method
 */
define("AUTH_TYPE_URI", 0);
define("AUTH_TYPE_AUTHORIZATION_BASIC", 1);
define("AUTH_TYPE_FORM", 2);

/**
 * Different Access token type
 */
define("ACCESS_TOKEN_URI",  0);   
define("ACCESS_TOKEN_BEARER",  1);
define("ACCESS_TOKEN_OAUTH", 2);
define("ACCESS_TOKEN_MAC", 3);

/**
* Different Grant types
*/
define("GRANT_TYPE_AUTH_CODE", 'authorization_code');
define("GRANT_TYPE_PASSWORD", 'password');
define("GRANT_TYPE_CLIENT_CREDENTIALS", 'client_credentials');
define("GRANT_TYPE_REFRESH_TOKEN", 'refresh_token');

/**
 * HTTP Methods
 */
define("HTTP_METHOD_GET", 'GET');
define("HTTP_METHOD_POST", 'POST');
define("HTTP_METHOD_PUT", 'PUT');
define("HTTP_METHOD_DELETE", 'DELETE');
define("HTTP_METHOD_HEAD", 'HEAD');

/**
 * HTTP Form content types
 */
define("HTTP_FORM_CONTENT_TYPE_APPLICATION", 0);
define("HTTP_FORM_CONTENT_TYPE_MULTIPART", 1);

$certificate_file = null;

// get the curloption ids  
$constants = get_defined_constants(true);
$curlOptLookup = preg_grep('/^CURLOPT_/', array_flip($constants['curl']));



// set the http procy here - leave blank to desactivate
define("HTTP_PROXY", '');



function simple_rest_client($url, $method=HTTP_METHOD_GET, $parameters = array(), $basicAuth  = array(), $cookieText='')
{

global $curlOptLookup; 

    // $curl_log = fopen(dirname(__FILE__).'/curllog.txt', 'a'); 

    $curl_options = array(
        CURLOPT_RETURNTRANSFER => true, //  Return response instead of printing.

        // CURLOPT_VERBOSE => true,        // Logs verbose output to STDERR
        // CURLOPT_STDERR  => $curl_log,   // Output STDERR log to file
        
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false, 
        CURLOPT_CUSTOMREQUEST  => $method

    );

    // set the cookies (as text)
    if ($cookieText) $headers[] = 'Cookie: '.$cookieText;

    switch($method) {
        case HTTP_METHOD_POST:
            if (!empty($parameters)) {
                $parameters = json_encode($parameters);
                $curl_options[CURLOPT_POSTFIELDS]= $parameters ;
                $headers[] = 'Content-Type: application/json';
            }
            break;


        case HTTP_METHOD_PUT:
            if (!empty($parameters)) {
                $parameters = json_encode($parameters);
                $curl_options[CURLOPT_POSTFIELDS]= $parameters ;
                $headers[] = 'Content-Type: application/json';
            }
            break;
        
        case HTTP_METHOD_DELETE:
        case HTTP_METHOD_GET:
            if (!empty($parameters)) {
                $url .= '?' . http_build_query($parameters);
            }
        default:
            break;
    }


    // set the URL 
    $orig_url=$url; // save original url
    $curl_options[CURLOPT_URL] = $url;

    // -- force autentification
    if (!empty($basicAuth)) {
        foreach($basicAuth as $username => $password) {
            $basicAuthStr = "$username:$password";
        }
        $curl_options[CURLOPT_USERPWD] = $basicAuthStr;
    }

    // -- globaly set headers 
    $curl_options[CURLOPT_HTTPHEADER]=  $headers;

    // -- launched th curl init 
    $ch = curl_init();

    // -- set the global options 
    curl_setopt_array($ch, $curl_options);

    
    // -- add proxy force proxy 
    if (HTTP_PROXY){
        curl_setopt($ch, CURLOPT_PROXY, HTTP_PROXY);     // PROXY details with port
    }

    // -- make the query 
    $t0=microtime(true); 

    $result = curl_exec($ch);

    // $result=true;

    // -- get the answer 
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $outauth = curl_getinfo($ch, CURLOPT_POST);
    if (ENABLE_DEBUG_INFO){

        $curl_options_translated=array();
         foreach($curl_options as $optionCode => $optionValue) {
            if (array_key_exists($optionCode, $curlOptLookup))
            $curl_options_translated[$curlOptLookup[(int)$optionCode]] = $optionValue;
            else
             $curl_options_translated[(int)$optionCode] = $optionValue;
        }

        $debug = array(
            'code' => $http_code,
            'url'=>$curl_options[CURLOPT_URL], 
            'options' => $curl_options_translated,
            'exec_time'=>sprintf("%01.3f", (microtime(true)-$t0)). "s "
        ); 
    }


    if ($curl_error = curl_error($ch)) {
        return array(
            'success' => false,
            'code' => $http_code,
            'error' => curl_error($ch).'('.curl_errno($ch).')',
            'debug'=> $debug
        );
    } else 
    if ($http_code>=400 ){
        return array(
            'success' => false,
            'code' => $http_code,
            'error' => $result,
            'debug'=> $debug
        );
    } else { 
        if ($content_type=="application/zip"){
            $filepath="test.zip"; 
            file_put_contents($filepath, $result);
            $result = filesize($filepath);
        } else {
            $json_decode = json_decode($result, true);
        }
    }

    curl_close($ch);
    // fclose($curl_log);
    
    return array(
        'success' => true,
        'response' => (null === $json_decode) ? $result : $json_decode,
        'code'=>$http_code,
        'content_type' => $content_type,
        'x_debug'=> $debug
    );
}








?>
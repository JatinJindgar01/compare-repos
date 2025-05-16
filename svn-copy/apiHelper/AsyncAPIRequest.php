<?php

require_once 'helper/ShopbookLogger.php';

class AsyncAPIRequest
{

    public static function post($url, $payload, $debug = false)
    {

        $cmd = "curl -X POST -H 'Content-Type: application/json'";
        $cmd .= " -d '" . $payload . "' " . "'" . $url . "'";
        if (!$debug) {
            $cmd .= " --connect-timeout 1 > /dev/null 2>&1 &";
        }

        global $logger;
        $logger->debug("Forking a new CURL Request => COMMAND: '$cmd'");
        exec($cmd, $output, $exit);
        $logger->debug("Forked CURL Request => EXIT VALUE: '$exit'; OUTPUT: " . print_r($output, true));

        return $exit == 0;
    }


    public static function ingestEvent($url, $payload, $orgId, $debug = false)
    {
        global $logger;
        $cmd = "curl -X POST -H 'Content-Type: application/json' -H 'Accept: application/json' " .
            "-H 'X-CAP-API-AUTH-KEY: capillary' -H 'X-CAP-API-AUTH-ORG-ID: ". $orgId ."'";
        $cmd .= " -d '" . $payload . "' " . "'" . $url . "'";
        if (!$debug) {
            $cmd .= " --connect-timeout 5 > /dev/null 2>&1 &";
        }

        $logger->debug("The url to be called: " . $url);
        $logger->debug("The data to be ingested: " . $payload);

        $logger->debug("Forking a new CURL Request => COMMAND: '$cmd'");
        exec($cmd, $output, $exit);
        $logger->debug("Forked CURL Request => EXIT VALUE: '$exit'; OUTPUT: " . print_r($output, true));

        return $exit == 0;
    }
}

?>
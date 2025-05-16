<?php

/**
 * Created by IntelliJ IDEA.
 * User: shanuj
 * Date: 16/12/16
 * Time: 5:24 PM
 */
class EventIngestionHelper
{
    /* Function to ingest the event to the capillary system
     @Params
    $eventName : the name of the event
    $description: the event description
    $timestamp: timestamp of event generation
    $attributes: key value pair with all properties related to the event
     * */
    public static function ingestEventAsynchronously($orgId, $eventName, $description, $eventTimestamp, $attributes)
    {
        require_once 'apiHelper/AsyncAPIRequest.php';
        global $logger;
        $requestData = array(); // this is the json data to be ingested

        $logger->info("Ingesting event to the capillary system");

        //$requestData = json_encode($requestData);
        $requestData["orgId"] = $orgId;
        $requestData["eventTimestamp"] = $eventTimestamp;
        $requestData["ingestionTimestamp"] = time();
        $requestData["description"] = str_replace("'", "", $description);
        $requestData["name"] = $eventName;
        $requestData["attributes"] = $attributes;

        $logger->debug("The data to be ingested: " . json_encode($requestData));
        global $cfg;
        $ipAddress = $cfg['srv']['event_ingestion_api']['0']['host'];
        if (!empty($ipAddress)) {
            $port = $cfg['srv']['event_ingestion_api']['0']['port'];
            $ingestionApiBaseUrl = 'http://' . $ipAddress;
            if (!empty($port)) {
                $ingestionApiBaseUrl .= ":" . $port;
            }
            $ingestionApiBaseUrl .= '/v2/internal/event/add';
            $logger->debug("Pushing event ingestion data " . json_encode($requestData) . " to " . $ingestionApiBaseUrl);

            AsyncAPIRequest::ingestEvent($ingestionApiBaseUrl, json_encode($requestData), $orgId);
        } else {
            $logger->info("Event ingestion service not found running");
        }
    }
}
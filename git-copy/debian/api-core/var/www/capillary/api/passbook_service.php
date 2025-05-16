<?php

/**
Service handler for the passbook feature

@author pigol
**/

ob_start();

require_once('common.php');
require_once('helper/passbook/PassbookUrlParser.php');
require_once('apiController/ApiPassbookController.php');
require_once 'helper/passbook/PassbookLogger.php';

global $logger;
$logger = new ShopbookLogger();

$url = $_SERVER['REQUEST_URI'];

$pblogger = new PassbookLogger();

$url_parser = new PassbookUrlParser($url, $_SERVER['QUERY_STRING']);
$body = file_get_contents("php://input");
$headers = apache_request_headers();

$pass_cntrl = new ApiPassbookController();

$logger->info("Query string: $url");

if($url_parser->getResource() == 'devices' && $url_parser->getMethod() == 'registrations')
{
    //new device is getting registered on our system
    if(strtoupper($url_parser->getHttpMethod()) == 'POST'){
        
         $logger->info("Registering a new device");
         try{
         	
         	$r = $pass_cntrl->registerNewDevice($url_parser->getDeviceIdentifier(), $url_parser->getPassIdentifier(), $url_parser->getPassSerial(),$body, $headers); 
         }
         catch (ItemNotFoundException $e){
         	
         	header('HTTP/1.1 404');
         	ob_flush();
         	$logger->info("Passbook Not Found".$e);
         	exit(0);
         }
         catch (UserDeviceMappingException $e){
         	
            header('HTTP/1.1 200 OK');             
            ob_flush();
            $logger->info("Device already registered ".$e);
            exit(0);
         }catch (AuthorizationFailureException $e){
         	
             header('HTTP/1.1 401 Unauthorized');
             ob_flush();
             $logger->info("Not authorized ".$e);
             exit(0);
         }
         catch (Exception $e){
         	
         	header('HTTP/1.1 '.$r);
         	ob_flush();
         	$logger->info("Some other error ".$e);
         	exit(0);
         }
         if($r == 201){
         	
         	 header('HTTP/1.1 201 Created');
             ob_flush();
             $logger->info("New device created");
             exit(0);
         }                                                                                                                              
    }else if(strtoupper($url_parser->getHttpMethod() == 'GET')){ //fetch the list of passes for this device
        
        $logger->info("Fetching the passes for the device");
        try{
        	
        	$passes = $pass_cntrl->getPassesForDevice($url_parser->getDeviceIdentifier(), $url_parser->getPassIdentifier(), $headers, $url_parser->getQueryParams());
        }
        catch(ItemNotFoundException $e){
        	
        	header('HTTP/1.1 204');
        	ob_flush();
        	$logger->info("No Matching Passes ".$e);
        	exit(0);
        }
        catch(Exception $e){
        	
        	header('HTTP/1.1 ');
        	ob_flush();
        	$logger->info("Some other error ".$e);
        	exit(0);
        }
        header('HTTP/1.1 200 OK');
        ob_flush();
        echo json_encode($passes);
        $logger->info("Returning Serial numbers for Passes ");
        exit(0);
    }else if(strtoupper($url_parser->getHttpMethod()) == 'DELETE'){  //unregister the device
        
        $logger->info("Unregistering a device");
        try{
        	
        	$r  = $pass_cntrl->unRegisterDevice($url_parser->getDeviceIdentifier(), $url_parser->getPassIdentifier(), $headers);
        }
        catch (ItemNotFoundException $e){
        	
        	header('HTTP/1.1 401');
        	ob_flush();
        	$logger->info("Unregister Failed ".$e);
        	exit(0);
        }
        catch (Exception $e){
        	
        	header('HTTP/1.1 ');
        	ob_flush();
        	$logger->info("Some other Error".$e);
        	exit(0);
        }
        if( $r == 200){
        	
       		header('HTTP/1.1 200 Unregistered');
        	ob_flush();
        	$logger->info("Device Unregistered");
        	exit(0);
        }
    }else{  //unknown method
    	
        $logger->info("Some random method called");
        header('HTTP/1.1 404 Resource not found');
        ob_flush();             
        exit(0);
    }    
}

if($url_parser->getResource() == 'passes' && $url_parser->getHttpMethod() == 'GET')
{
    $logger->info("Fetching the loyalty passes");
    
    try{
    	
    	$file = $pass_cntrl->getLatestLoyaltyPass($url_parser->getPassIdentifier(), $url_parser->getPassSerial(), $headers['Authorization']);
    }
    Catch(Exception $e){
    	
    	$logger->error("ItemNotFoundException".$e);
    	header('HTTP/1.1 401');
    	ob_flush();
    	exit(0);
    }
    catch (Exception $e){
    	
    	header('HTTP/1.1 ');
    	ob_flush();
    	$logger->info("Some other Error".$e);
    	exit(0);
    }
    
    // Stream the file to the client 
    header("Content-Type: application/vnd.apple.pkpass");
    header("Content-Length: " . filesize($file)); 
    header("Content-Disposition: attachment; filename=\"loyalty.pkpass\"");
    header('Content-Transfer-Encoding: binary'); 
    
    $contents = file_get_contents($file, filesize($file));
    echo $contents;
    ob_flush();    
}

/**
 * there is some error in our system
 */

if($url_parser->getResource() == 'log'){
	
    $logger->info("Logging Passbook logs into passbook.log");
    $payload = json_decode($body,true);
	$pblogger->error(print_r($payload,true));    
}

if($url_parser->getResource() == 'coupon' && $url_parser->getHttpMethod() == 'GET'){
    
    $coupon_serial_no = urldecode($url_parser->getPassSerial());
    $logger->info("Fetching the coupon " . $coupon_serial_no);    
    try{
    	
    	$file = $pass_cntrl->getCouponPass($coupon_serial_no);
    }
    catch (ItemNotFoundException $e){
    	
    	$logger->error("ItemNotFoundException".$e);
    	header('HTTP/1.1 401');
    	ob_flush();
    	exit(0);
    }
    catch (Exception $e){
    	
    	$logger->error("Some Other Error");
    	header('HTTP/1.1 ');
    	ob_flush();
    	exit(0);
    }
    // Stream the file to the client 
    header("Content-Type: application/vnd.apple.pkpass"); 
    header("Content-Length: " . filesize($file)); 
    header("Content-Disposition: attachment; filename=\"coupon.pkpass\"");      
    header('Content-Transfer-Encoding: binary'); 
    
    $contents = file_get_contents($file, filesize($file));
    echo $contents;
    ob_flush();    
}else{
	
    header('HTTP/1.1 404 Resource not found');
}
?>

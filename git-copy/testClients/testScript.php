<?php
ini_set('display_errors', 'on');
$cheetah_path = $_ENV['DH_WWW']."/cheetah";
set_include_path(get_include_path() .PATH_SEPARATOR . $cheetah_path );
include_once 'helper/Errors.php';
include_once 'helper/INIParser.php';
include_once 'common.php';
include_once 'thrift/base.php';
$logger = new ShopbookLogger();
$supported_clients = array( 'authentication',
							'organization',
							'shopbook' );
ob_start();
if( !$argv[1] ){
	
	die( "Thrift arguments file not passed.. exiting..." );
}else{
	
	$conf_path = $argv[1];
	echo "Parsing Thrift arguments ini file $conf_path \n";
// 	$thrift_arguments = parse_ini_file( $conf_path, true ) or die( "Error parsing thrift args" );
	$thrift_arguments = INIParser::parse( $conf_path, true ) or die( "Error parsing thrift args" );
	echo print_r( $thrift_arguments, true );
	foreach( $thrift_arguments as $client => $methods ){
		
		if( in_array( $client, $supported_clients ) ){
		
			echo "Loading Client $client...\n";
			$thrift_client_file_name = $client;
			$thrift_client_class_name = ucfirst( $client )."ThriftClient";
			try{
					
				include_once $thrift_client_file_name.".php";
				$ref_class = new ReflectionClass( $thrift_client_class_name );
				$ref_instance = $ref_class->newInstanceArgs( array() );
				foreach( $methods as $method => $args ){
					
					echo "$thrift_client_class_name : Calling $method with arguments ".print_r( $args, true )."\n";
					$ref = new ReflectionMethod( $thrift_client_class_name, $method );
					echo "$thrift_client_class_name : $method( ".implode( ' , ', $args )." ) \n";
					echo "Result: ".print_r( $ref->invokeArgs( $ref_instance, $args), true );
				}
			}catch( Exception $e ){
					
				echo "Exception: $e";
			}
		}else{
		
			echo "Client not supported";
		}
	}
}
$content = ob_get_contents();
$f = fopen( "TestClientOutput.txt", "w" );
fwrite($f, $content);
fclose($f);
?>
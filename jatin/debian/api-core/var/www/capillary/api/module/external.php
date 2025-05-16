<?php

/**
 * Central place for the external API for the IPhone.
 * Putting all the kachda code in one place so that it 
 * is easier to clean it up later
 * @author piyush
 *
 */


class ExternalModule extends BaseModule {

	private $db;	
	private $org_id;
	private $stores;
	private $lm;
  	//private $logger; 
 	
	function __construct() {
		
    //global $logger;
	  parent::__construct();
	   	
		$this->db = new Dbase('users');
		$this->org_id = $this->currentorg->org_id;
		$this->lm = new ListenersMgr($this->currentorg);
    //$this->logger = $logger; 
   }

   
    /**
    	API for adding a customer in the InTouch System
    **/
	function addCustomerApiAction(){
		
		$xml_string = <<<EOXML
			<root>
				<customer>
					<name>Arun Sharma</name>
					<mobile>9876543210</mobile>
					<email>asharma@mail.com</email>
					<gender>M</gender>
					<age>25</age>
					<birthday>21-02-1985</birthday>
					<phone_id></phone_id>
				</customer>
				<org_id>343</org_id>
			</root>
EOXML;

		$xml_string = $this->getRawInput();
	    $this->logger->debug("Input xml: $xml_string");
        
		//error handiling
		$element = Xml::parse( $xml_string );
	
		$elems = $element->xpath('/root/customer');
		
		$name = ( string ) $e->name;
		$mobile = ( string ) $e->mobile;
		$email = ( string ) $e->email;
		$gender = ( string ) $e->gender;
		$age = ( string ) $e->age;
		$birthday = ( string ) $e->birthday;
		$phone_id = ( string ) $e->phone_id;
			
		//mandatory fields : name, mobile or email			
		if( !$name )
			//throw error
			
		if( !$mobile && !$email )
			//throw error
		
		
		//sends dummy data
		// TODO change to exact id
		$return_call = <<<EOXML
			<root>
				<status>SUCCESS</status>
				<customer_id>11756</customer_id>
			</root>
EOXML;

		$this->data['external'] = $return_call;
	}

	/*
	 * API for uploading the image of the customer try it on feature
	 */
	function uploadPhotoApiAction(){
		
		$prefix = substr($_SERVER['PHP_SELF'], 0, stripos($_SERVER['PHP_SELF'], 'api_service.php', 1) - 1);
		$parts = pathinfo($_SERVER['SCRIPT_FILENAME']);
		$dir_path = $parts['dirname'] . '/images/';
		$this->logger->debug("dir path: " . $dir_path);
		
		foreach($_FILES as $k=>$v){
		  $this->logger->debug("v object: " . print_r($v, true));	
	          $fname = $v['name'];
		  $tmp_name = $v['tmp_name'];
		  $image = file_get_contents($tmp_name);
		  file_put_contents("$dir_path/$fname", $image);
		}
	
		$url = "http://" . $_SERVER['SERVER_NAME'] . $prefix  . "/images/$fname";
		$this->logger->debug("image path: $url");
		$response = <<<EOXML
		   <root>
			<image>
			  <status>SUCCESS</status>	
			  </url>$url</url>	
			</image>
		   </root>	
EOXML;
		$this->data['external'] = $response;
	}
	
	
	/**
		API for editing customer details
	**/
	function editCustomerApiAction(){
		
		$xml_string = <<<EOXML
			<root>
				<customer>
					<customer_id>11756</customer_id>
					<name>Arun Sharma</name>
					<mobile>9876543210</mobile>
					<email>asharma@mail.com</email>
					<gender>M</gender>
					<age>25</age>
					<birthday>21-02-1985</birthday>
				</customer>
			</root>
EOXML;

		$xml_string = $this->getRawInput();
		
		//error handiling
		$element = Xml::parse( $xml_string );
	
		$e = $element->xpath('/root/customer');

		$name = ( string ) $e->name;
		$mobile = ( string ) $e->mobile;
		$email = ( string ) $e->email;
		$gender = ( string ) $e->gender;
		$age = ( string ) $e->age;
		$birthday = ( string ) $e->birthday;
		$phone_id = ( string ) $e->phone_id;
		$customer_id = ( int ) $e->customer_id;
			
		//mandatory fields : customer_id name, mobile or email
		if( !$customer_id )
			'';//throw error
						
		if( !$name )
			'';//throw error
			
		if( !$mobile && !$email )
			'';//throw error

		$return_call = <<<EOXML
			<root>
				<customer>
					<status>SUCCESS</status>
					<customer_id>11756</customer_id>
					<name>Arun Verma</name>
					<mobile>9876543211</mobile>
					<email>asharma@gmail.com</email>
					<gender>M</gender>
					<age>25</age>
					<birthday>21-02-1985</birthday>
				</customer>
			</root>
EOXML;
		
		$this->data['external'] = $return_call;
		
	}
    
	
	/**
	    API for fetching loyalty points information
	    of the customer	
	**/
	function loyaltyDetailsApiAction( $customer_id ){

		$return_call = <<<EOXML
			<root>
				<details>
					<status>SUCCESS</status>
					<lifetime_points>100</lifetime_points>
					<current_points>60</current_points>
					<redeemed>20</redeemed>
					<expired>20</expired>
				</details>
			</root>
EOXML;
		
		$this->data['external'] = $return_call;

	}
    
	/**
		Checkin In a customer into a store
	**/
	function checkinApiAction( $customer_id, $store_id, $store_code ){
		
		$return_call = <<<EOXML
			<root>
				<checkin>
					<status>SUCCESS</status>
					<points_awarded>100</points_awarded>
					<points_total>700</points_total>
					<voucher_description>Get 10% off on Jeans#$#Get 20% off on Shirt</voucher_description>
					<voucher_code>2323xyz#$#12sd1</voucher_code>
				</checkin>
			</root>
EOXML;
		
		$this->data['external'] = $return_call;

	}
	
	/**
	 * issue a voucher for store to the customer
	 */
	function issueStoreVoucherApiAction( $customer_id, $store_id, $store_code ){
	
        global $logger;
        $logger->debug("customer_id: $customer_id store_id: $store_id store_code: $store_code");
     
		$return_call = <<<EOXML
			<root>
				<voucher>
					<status>SUCCESS</status>
					<voucher_description>Get 10% off on Jeans#$#Get 50% off on T-Shirts</voucher_description>
					<voucher_code>2323ABC#$#4ygh5</voucher_code>
				</voucher>
			</root>
EOXML;
		
		$this->data['external'] = $return_call;
		
	}
	
	/**
	 * Returns the store information to the customer
	 */
	function storesApiAction( $org_id = 0 ){
		$this->data['external'] = $this->getStores();
	}
	
	/**
	 * Returns the product information to the customer
	 * based on the inventory item code passed to the API
	 */
	function productDetailsApiAction( $org_id, $customer_id, $product_code ){
		
		$return_call = <<<EOXML
			<root>
				<product>
					<status>SUCCESS</status>
					<price>200</price>
					<description>Mens jeans</description>
					<attribute_keys>Size,Color,Waist</attribute_keys>
					<attribute_values>42,Black,32</attribute_values>
					<points_awarded>120</points_awarded>
					<voucher_description>Get 10% off on Jeans</voucher_description>
					<voucher_code>232XYZ</voucher_code>
				</product>
			</root>
EOXML;
		
		$this->data['external'] = $return_call;
		
	}
	
	/**
	 * Returns the latest news of the organization 
	 */	
	function getNewsApiAction( $org_id, $start_id = 0 ){
		
		$return_call = <<<EOXML
			<root>
				<news>
					<status>SUCCESS</status>
					<data>
						<![CDATA[NEW DELHI: Almost repeating what he said in a conclave earlier in the day, PM Manmohan Singh defended his government in connection with the 2008 cash-for-vote allegations. "The government cannot confirm the veracity, content or even existence of such communication," the PM told the Lok Sabha. Whistleblower website WikiLeaks has leaked US cables according to which an aide of Congress leader Satish Sharma had told a US embassy official that MPs were paid for a majority confidence vote in 2008.
"The government rejects the allegations of bribery as mentioned in the WikiLeaks... nobody from Congress or government is engaged in any unlawful act," he said. The PM called the WikiLeaks communications "speculative, unverified and unverifiable"]]>
					</data>
					<title>Govt can not confirm veracity of cables leaked by WikiLeaks: PM</title>
					<date>2011-01-23 14:04:09</date>
					<thumbnail>http://sb.capillary.co.in/images/news_thumb_1.jpg</thumbnail>
					<images>http://sb.capillary.co.in/images/news_1.jpg#$#http://sb.capillary.co.in/images/news_2.jpg</images>
				</news>
				<news>
					<status>SUCCESS</status>
					<data>puma news>
						<![CDATA[ This weekend sees one of the world’s most fierce derby matches take place as Milan’s two clubs come face to face at the world famous San Siro. The match takes on extra significance as AC Milan currently lead their great rivals by just two points as the season reaches its climax.  ]]>
					</data>
					<title>Will Inter Overtake AC Milan?</title>
					<date>2011-02-23 10:04:09</date>
					<thumbnail>http://sb.capillary.co.in/images/news_thumb_2.jpg</thumbnail>
					<images>http://sb.capillary.co.in/images/news_2.jpg</images>
				</news>
				<news>
					<status>SUCCESS</status>
					<data>
						<![CDATA[German sportswear brand Puma AG tapped head of strategy Franz Koch as its new boss, heaping responsibility for meeting long-overdue sales targets on the 32-year-old former field hockey professional. Mr. Koch will take over from Chief Executive Jochen Zeitz next month. Mr. Zeitz has been promoted to oversee the sports lifestyle division of Puma's owner, French retail group PPR SA. Puma is the only brand in ...]]>
					</data>
					<title>Puma Names New CEO</title>
					<date>2011-04-02 01:04:09</date>
					<thumbnail>http://sb.capillary.co.in/images/news_thumb_4.jpg</thumbnail>
					<images>http://sb.capillary.co.in/images/news_5.jpg</images>
				</news>
			</root>
EOXML;
		
		$this->data['external'] = $return_call;
		
	}
	
	/**
	 * Returns the latest videos of the organization
	 * to the app
	 */
	function getVideoApiAction( $org_id = 0 ){
		
		$return_call = <<<EOXML
			<root>
				<video>
					<status>SUCCESS</status>
					<url>http://www.youtube.com/watch?v=VD_9O0MlUu0</url>
					<thumbnail>http://org.capillary.co.in/images/favicon.ico</thumbnail>
					<title>Aa Chalke Tujhe-Door Gagan Ki Chahon Mein</title>
				</video>
			</root>
EOXML;
		
		$this->data['external'] = $return_call;		
	}
	
	/**
	 * Returns the latest photos of the organization
	 * to the customer
	 */
	function getPhotosApiAction( $org_id ){
		
		$return_call = <<<EOXML
			<root>
				<photo>
					<status>SUCCESS</status>
					<url>http://sb.capillary.co.in/images/photos_1.jpg</url>
					<thumbnail>http://sb.capillary.co.in/images/photo_thumb_1.jpg</thumbnail>
					<title>Puma New Sports Collection</title>
				</photo>
				<photo>
					<status>SUCCESS</status>
					<url>http://sb.capillary.co.in/images/photos_2.jpg</url>
					<thumbnail>http://sb.capillary.co.in/images/photo_thumb_2.jpg</thumbnail>
					<title>Puma New Tennis Collection</title>
				</photo>
				<photo>
					<status>SUCCESS</status>
					<url>http://sb.capillary.co.in/images/photos_3.jpg</url>
					<thumbnail>http://sb.capillary.co.in/images/photo_thumb_3.jpg</thumbnail>
					<title>Puma Soccer Collection</title>
				</photo>
				<photo>
					<status>SUCCESS</status>
					<url>http://sb.capillary.co.in/images/photos_4.jpg</url>
					<thumbnail>http://sb.capillary.co.in/images/photo_thumb_4.jpg</thumbnail>
					<title>Puma Lawn Tennis Collection</title>
				</photo>
			</root>
EOXML;
		
		$this->data['external'] = $return_call;		
	}


	/**
	 * Returns the main menu photos of the organization
	 * to the customer
	 */
	function getMainMenuPhotosApiAction( $org_id=0 ){
		
		$return_call = <<<EOXML
			<root>
				<photo>
					<status>SUCCESS</status>
					<url>http://sb.capillary.co.in/images/menu_1.jpg</url>
					<link>http://www.puma.com</link>
				</photo>
				<photo>
					<status>SUCCESS</status>
					<url>http://sb.capillary.co.in/images/menu_2.jpg</url>
					<link>http://www.puma.com</link>
				</photo>
				<photo>
					<status>SUCCESS</status>
					<url>http://sb.capillary.co.in/images/menu_3.jpg</url>
					<link>http://www.puma.com</link>
				</photo>
				<photo>
					<status>SUCCESS</status>
					<url>http://sb.capillary.co.in/images/menu_4.jpg</url>
					<link>http://www.puma.com</link>
				</photo>
				<photo>
					<status>SUCCESS</status>
					<url>http://sb.capillary.co.in/images/menu_5.jpg</url>
					<link>http://www.puma.com</link>
				</photo>
			</root>
EOXML;
		
		$this->data['external'] = $return_call;		
	}



	private function getStores()
	{
	   $return_call = <<<EOXML
<root>
	<store>
		<status>SUCCESS</status>
		<name>ThePUMAStoreBangalore</name>
		<address>Shop#202-204,2ndfloorGarudaMall,MagrathMall-83-Bangalore,India560025</address>
		<latitude>12.967</latitude>
		<longitude>77.6</longitude>
		<number>08040913608</number>
		<city>Bangalore</city>
		<state>Karnataka</state>
		<country>India</country>
	</store>
	<store>
		<status>SUCCESS</status>
		<name>ThePUMAOutletBangalore</name>
		<address>92/3,80feetRd,OppBigBazaar,BanshankariStageIII-57-Bangalore,India560085</address>
		<latitude>13.032</latitude>
		<longitude>77.69</longitude>
		<number>08041711671</number>
		<city>Bangalore</city>
		<state>Karnataka</state>
		<country>India</country>
	</store>
	<store>
		<status>SUCCESS</status>
		<name>ThePUMAOutletBangalore</name>
		<address>MaruthiComplex,MarathalliMainRoad-77-Bangalore,India560037</address>
		<latitude>12.959</latitude>
		<longitude>77.70</longitude>
		<number>08041487495</number>
		<city>Bangalore</city>
		<state>Karnataka</state>
		<country>India</country>
	</store>
	<store>
		<status>SUCCESS</status>
		<name>ThePUMAStoreBangalore</name>
		<address>No.216BrigadeRoad,CivilStation-78-Bangalore,India560001</address>
		<latitude>13.03</latitude>
		<longitude>77.69</longitude>
		<number>08040913054</number>
		<city>Bangalore</city>
		<state>Karnataka</state>
		<country>India</country>
	</store>
	<store>
		<status>SUCCESS</status>
		<name>ThePUMAStoreBangalore</name>
		<address>No.500,CMHRoadIndiragar-78-Bangalore,India560038</address>
		<latitude>13.03</latitude>
		<longitude>77.69</longitude>
		<number>08041522090</number>
		<city>Bangalore</city>
		<state>Karnataka</state>
		<country>India</country>
	</store>
	<store>
		<status>SUCCESS</status>
		<name>ThePUMAStoreNewDelhi</name>
		<address>No.M-19,GreaterKailashMBlockMainMarket,Part-1-78-NewDelhi,India110048</address>
		<latitude>28.601</latitude>
		<longitude>77.221</longitude>
		<number>+911146534302</number>
		<city>NewDelhi</city>
		<state>NewDelhi</state>
		<country>India</country>
	</store>
	<store>
		<status>SUCCESS</status>
		<name>ThePUMAStoreNewDelhi</name>
		<address>Shopno's-172,FirstFloor,UnitechMall,OppAttaMarket,Sector-38ANoida.Up-83-NewDelhi,India201301</address>
		<latitude>28.611</latitude>
		<longitude>77.36</longitude>
		<number>+911204245344</number>
		<city>Noida</city>
		<state>UttarPradesh</state>
		<country>India</country>
	</store>
	<store>
		<status>SUCCESS</status>
		<name>ThePUMAStoreNewDelhi</name>
		<address>Shopno.BG-06,AnsalPlaza-80-NewDelhi,India110049</address>
		<latitude>28.55</latitude>
		<longitude>77.21</longitude>
		<number>+911146037890</number>
		<city>NewDelhi</city>
		<state>NewDelhi</state>
		<country>India</country>
	</store>
	<store>
		<status>SUCCESS</status>
		<name>ThePUMAStoreNewDelhi</name>
		<address>PumaStore,ShopNo.1,17A/22-80-,WEA,AjmalKhanRoad,KarolBaghNewDelhi,India110005</address>
		<latitude>28.65</latitude>
		<longitude>77.19</longitude>
		<number>+911146243386</number>
		<city>NewDelhi</city>
		<state>NewDelhi</state>
		<country>India</country>
	</store>
	<store>
		<status>SUCCESS</status>
		<name>ThePUMAStoreNewDelhi</name>
		<address>UB-11,BunglowRoad,KamlaNagar-85-NewDelhi,India110007</address>
		<latitude>28.68</latitude>
		<longitude>77.20</longitude>
		<number>+911147046678</number>
		<city>NewDelhi</city>
		<state>NewDelhi</state>
		<country>India</country>
	</store>
	<store>
		<status>SUCCESS</status>
		<name>ThePUMAStoreOverlandPark</name>
		<address>11767West95thStreet,Space#146OverlandPark,KS66214</address>
		<latitude>38.96</latitude>
		<longitude>-94.71</longitude>
		<number>913.438.2700</number>
		<city>OverlandPark</city>
		<state>Kansas</state>
		<country>UnitedStates</country>
	</store>
	<store>
		<status>SUCCESS</status>
		<name>ThePUMAStorePlano</name>
		<address>6121W.ParkBlvd.,SpaceA-220Plano,TX75093</address>
		<latitude>33.039</latitude>
		<longitude>-96.802</longitude>
		<number>972.202.5530</number>
		<city>Plano</city>
		<state>Texas</state>
		<country>UnitedStates</country>
	</store>
	<store>
		<status>SUCCESS</status>
		<name>ThePUMAStoreDallas</name>
		<address>8687NorthCentralExpressway-56-,SuiteR1-1428Dallas,TX75225</address>
		<latitude>32.85</latitude>
		<longitude>-96.78</longitude>
		<number>214.363.5756</number>
		<city>Dallas</city>
		<state>Texas</state>
		<country>UnitedStates</country>
	</store>
	<store>
		<status>SUCCESS</status>
		<name>ThePUMAStoreMcLean</name>
		<address>1961ChainBridgeRd,Space#E2UMcLean,IL22102</address>
		<latitude>40.52</latitude>
		<longitude>-89.26</longitude>
		<number>703.893.0145</number>
		<city>McLean</city>
		<state>Illinois</state>
		<country>UnitedStates</country>
	</store>
	<store>
		<status>SUCCESS</status>
		<name>ThePUMAOutletRoundRock</name>
		<address>4401N.Highway35-52-,Suite163RoundRock,TX78664</address>
		<latitude>30.50</latitude>
		<longitude>-97.66</longitude>
		<number>512.930.5751</number>
		<city>RoundRock</city>
		<state>Texas</state>
		<country>UnitedStates</country>
	</store>
	<store>
		<status>SUCCESS</status>
		<name>ThePUMAStoreGlasgow</name>
		<address>91BuchananStreet-57-Glasgow,UnitedKingdomG13HB</address>
		<latitude>55.860</latitude>
		<longitude>-4.250</longitude>
		<number>+44(0)1412487177</number>
		<city>Glasgow</city>
		<state>Glasgow</state>
		<country>UnitedKingdom</country>
	</store>
	<store>
		<status>SUCCESS</status>
		<name>ThePUMAStoreMiltonKeynes</name>
		<address>MidsummerPlaceShoppingCentre,Unit8-77-,67MidsummerPlaceMiltonKeynes,UnitedKingdomMK93GB</address>
		<latitude>52.0410119</latitude>
		<longitude>-0.7569761</longitude>
		<number>+44(0)1908395010</number>
		<city>MiltonKeynes</city>
		<state>MiltonKeynes</state>
		<country>UnitedKingdom</country>
	</store>
	<store>
		<status>SUCCESS</status>
		<name>ThePUMAOutletBicester</name>
		<address>50PingleDrive-53-Bicester,UnitedKingdomOX266WD</address>
		<latitude>51.8917671</latitude>
		<longitude>-1.155574</longitude>
		<number>00441869252225</number>
		<city>Bicester</city>
		<state>Bicester</state>
		<country>UnitedKingdom</country>
	</store>
	<store>
		<status>SUCCESS</status>
		<name>ThePUMAStoreLondon</name>
		<address>ArielWayLondon,UnitedKingdomW127GD</address>
		<latitude>51.5094311</latitude>
		<longitude>-0.2217731</longitude>
		<number>+44(0)2087491668</number>
		<city>London</city>
		<state>London</state>
		<country>UnitedKingdom</country>
	</store>
	<store>
		<status>SUCCESS</status>
		<name>ThePUMAStoreLondon</name>
		<address>52-55CarnabyStreet-53-London,UnitedKingdomW1F9QE</address>
		<latitude>51.5123023</latitude>
		<longitude>-0.1379353</longitude>
		<number>+44(0)2074390221</number>
		<city>London</city>
		<state>London</state>
		<country>UnitedKingdom</country>
	</store>
</root>
EOXML;

	return $return_call;		
	}
	
 public function getClientNotesForCustomerApiAction()      
 {
	$this->logger->debug("Adding a family in the system");
   	$xml_string =<<<EOXML
   		<root>
   		  <customer>
	   		<mobile>919980616752</mobile>	
   		   	<email>piyush.goel@capillary.co.in</email>
                        <external_id>1212</external_id>
   		  </customer> 	
   		</root>
EOXML;
   	
   	$xml_string = $this->getRawInput();
   	$this->logger->debug("Input xml: $xml_string");
   //Verify the xml strucutre
	if(Util::checkIfXMLisMalformed($xml_string))
	{		
		$api_status = array(
				'key' => getResponseErrorKey(ERR_RESPONSE_BAD_XML_STRUCTURE),
				'message' => getResponseErrorMessage(ERR_RESPONSE_BAD_XML_STRUCTURE) 
		);
		$this->data['api_status'] = $api_status;
		return;		
	}
   	
        
        $responses = array();

	$parsed_object = Xml::parse($xml_string);
	$customer = $parsed_object->xpath('/root/customer');
	$this->logger->debug("customer object: " . print_r($customer, true));

        $pref_array = array(
                             array('note' => 'Customer likes Denim', 'date' => '2012-04-06'),
                             array('Customer likes Casual Shirts', 'date' => '2012-04-13'),
                             array('Likes to watch Soccer', 'date' => '2012-05-05'),
                             array('Favorite Movie: Godfather', 'date' => '2012-05-17'),  
                             array('Remind customer about upcoming sale on Jean' , 'date' => '2012-06-01')  
                           );     

        foreach($customer as $c)
        {
            $mobile = trim((string)$c->mobile);
            $email = trim((string)$c->email);
            $external_id = trim((string)$c->external_id);    

            $start_idx = rand(0, 3);    
            $c_response = array(        
                                 'mobile' => $mobile,
                                 'email' => $email,
                                 'external_id' => $external_id,
                                 'notes' => array_slice($pref_array, $start_idx, count($pref_array) - $start_idx)                                       
                               );
            array_push($responses, $c_response);
        }
        
        $this->data['api_status'] = array(
					'key' => 'ERR_RESPONSE_SUCCESS',
					'message' => 'Operation Successful'
				        );
	$this->data['responses'] = $responses;
 }





}
?>

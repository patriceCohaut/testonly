<?php

/******************************************************************************
 * OmniPCX Open Gateway 
 * @category   SAMPLE CODE
 * @package    O2G
 * @author     Patrice COHAUT <patrice.cohaut@al-enterprise.com>
 * @copyright  2018  ALE
 * @version    0.9.9
 ******************************************************************************/


/* 
For onboarding and playground purpose
This is a simple API proxy running on PHP server side. 
This proxy take POST request from a Client and call the O2G REST API with propper ressources and return the response to the initial Request

POST
URI : {URL}/o2g_api_proxy.php

PARAMETERS :

	method  :
	 
		create_session | refresh_session | get_session_info | logout_session
		get_user_status
		get_status
		get_pbxs_list
		get_users_list | get_user_details 
		get_user_routing | forward_to | cancel_forward
		get_subscriber_details | put_subscriber
		get_model
		make_call | get_call | basiccall_answer | basiccall_drop
		get_comlog | delete_comlog | ack_comlog
		get_mailboxes | 
		subscribe | 

	token : 
	loginName : 
	companyPhone :
	nodeId : 
	recordId : 
	callee : 

*/



	define("_VERSION", '0.9.9');
	require_once("generic_API_client.php");


	// **** DEFINE HERE General O2G URI ***   
	define("ROXE_FQDN", 'URL:PORT/api/rest');


	// main variable containing the API response stream
	$rest_sequence=array();

	// get POST parameters 
	$method = $_POST['method']; 
	$TOKEN=$_POST['token']; // the received token if any 
	$cookieText='AlcUserId='. $TOKEN; //cookieTest to be used for calling the API

	switch ($method) {

		// create a session 
		case "create_session":

			// check on present of login and password and early exit if not presents 
			if (!isset($_POST['username']) || !isset($_POST['password']) ) 
				o2g_early_die('400', 'bad-request missing login and/or password in auth '); 

			// -- step 1 - autentifaction 
		    $r= simple_rest_client(ROXE_FQDN."/authenticate?version=1.0", 'GET',[], array( $_POST['username'] => $_POST['password']));
			$rest_sequence['auth'] = $r; // debug 

			if ($r['success']) {

				// get the access TOKEN  
			    $token = $r['response']['credential']; 

			    // save the token in a cookie called AlcUserId and cookieTest to be used for calling the API 
			    $TOKEN =$token; 
        		setcookie("AlcUserId" ,$TOKEN , (time() + 31536000), '/');
        		$cookieText='AlcUserId='. $TOKEN;

        		// -- create the session  
			   	$rest_params = array(
			       'applicationName' => 'TEST_PCOHAUT',   # the application name = VENDOR TOKEN if exist   
	    		);
			    $r= simple_rest_client(ROXE_FQDN."/1.0/sessions", 'POST', $rest_params, [], $cookieText);
			    $rest_sequence['sessions'] = $r; 


			} else {

				// delete the cookie
				setcookie('AlcUserId', "", time()-3600, '/');  

				// send the result code from http method and quit  
				http_response_code($r['code']); die(); 
			}

		break; 


		// -- refresh current session  
		case "refresh_session": 
			
				$cookieText= roxe_verify_valid_cookie($action, $method);

			   	$rest_params = array();
			    $r= simple_rest_client(ROXE_FQDN."/1.0/sessions/keepalive", 'POST',$rest_params, [], $cookieText);
			    $rest_sequence = $r;

			    if (!$r['success']) { http_response_code($r['code']); die(); }

		break;


		// -- get session informations  
		case "get_session_info": 
			
				$cookieText= roxe_verify_valid_cookie($action, $method);

			   	$rest_params = array();
			    $r= simple_rest_client(ROXE_FQDN."/1.0/sessions/", 'GET',$rest_params, [], $cookieText);
			    $rest_sequence = $r;

			    if (!$r['success']) { http_response_code($r['code']); die(); }

		break;  

		// -- Logout current session  
		case "logout_session": 
			
				$cookieText= roxe_verify_valid_cookie($action, $method);

			   	$rest_params = array();
			    $r= simple_rest_client(ROXE_FQDN."/1.0/sessions", "DELETE", $rest_params, [], $cookieText);
			    $rest_sequence = $r;

			    if (!$r['success']) { http_response_code($r['code']); die(); }
			    else {
			    	// delete the cookie and continue
					setcookie('AlcUserId', "", time()-3600, '/');  
			    }

		break; 


		// -- COMBO - get 360 details on a given loginName (user)  
		case "get_user_status" : 

	    	$cookieText= roxe_verify_valid_cookie($action, $method); 

	    	$LOGINNAME = trim($_POST['loginName']); 
	    	$rest_params = array('loginName' => $LOGINNAME);

		    // get session 
		   	$o2g_url = ROXE_FQDN."/1.0/sessions" ; 
		    $r= simple_rest_client($o2g_url, 'GET',[], [], $cookieText);
		    $rest_sequence['response']['sessions'] = $r['response']; 

    		// get user details
		   	$o2g_url = ROXE_FQDN."/1.0/users"."/".$LOGINNAME ; 
		    $ru= simple_rest_client($o2g_url, 'GET',[], [], $cookieText);
		    $rest_sequence['response']['user']=$ru['response'] ;

		    // get telephony device status
		   	$o2g_url = ROXE_FQDN."/1.0/telephony/devices" ; 
		    $ru= simple_rest_client($o2g_url, 'GET',$rest_params, [], $cookieText);
		    $rest_sequence['response']['devices']=$ru['response']['deviceStates'] ;


		    // get telephony call status 
		   	$o2g_url = ROXE_FQDN."/1.0/telephony/calls" ; 
		    $ru= simple_rest_client($o2g_url, 'GET',$rest_params, [], $cookieText);
		    $rest_sequence['response']['calls']=$ru['response'] ;

		    // get telephony state
		   	$o2g_url = ROXE_FQDN."/1.0/telephony/state" ; 
		    $ru= simple_rest_client($o2g_url, 'GET',$rest_params, [], $cookieText);
		    $rest_sequence['response']['state']=$ru['response'] ;

		    // get routing
		   	$o2g_url = ROXE_FQDN."/1.0/routing/state" ; 
		    $ru= simple_rest_client($o2g_url, 'GET',$rest_params, [], $cookieText);
		    $rest_sequence['response']['routing']=$ru['response'] ;

		    // get forward route
		   	$o2g_url = ROXE_FQDN."/1.0/routing/forwardroute" ; 
		    $ru= simple_rest_client($o2g_url, 'GET',$rest_params, [], $cookieText);
		    $rest_sequence['response']['routing/forwardroute']=$ru['response'] ;

		break;  


		// -- Get system stats 
		case "get_status" : 

			$cookieText= roxe_verify_valid_cookie($action, $method); 

	    	$o2g_url = ROXE_FQDN."/1.0/system/status" ; 
	    	$r= simple_rest_client($o2g_url, 'GET',[], [], $cookieText);
	    	$rest_sequence = $r; 
		 
		break; 


		case "get_pbxs_list" : 

			$cookieText= roxe_verify_valid_cookie($action, $method); 

			 // get the list of PBXs 
		   	$o2g_url = ROXE_FQDN."/1.0/pbxs" ; 
		   	$rest_params = array();
		    $r= simple_rest_client($o2g_url, 'GET',$rest_params, [], $cookieText);
		    $rest_sequence= $r; 

		    if ($r['success']){

		    	foreach ( $r['response']['nodeIds'] as $key => $node) {
		    		$NODEID = $node; 
		    		
		    		// get instances  : the fisrt level model 
				    $o2g_url = ROXE_FQDN."/1.0/pbxs/".$NODEID."/instances" ; 
				    $ri= simple_rest_client($o2g_url, 'GET',[], [], $cookieText);

				    // convert attributes into name:value pairs 
				    $attrs = array(); 
				    foreach ($ri['response']['attributes'] as $key2 => $attribute) {
				    	$x=''; $y=''; 
				    	foreach ($attribute as $keya => $valuea) {
				    		if ($keya=="name") $x=$valuea; 
				    		if ($keya=="value") $y=$valuea[0];
				    	}
				    	$attrs[$x] = $y; 
				    }
				    $rest_sequence['response']['attributes'][]=$attrs ; 
				    // $rest_sequence['response']['genuine_attributes'][]=$ri['response']['attributes'] ; 
		    	}
		    }
		   				 
		break; 


		// -- Get list of users / logins 
		case "get_users_list" : 

			$cookieText= roxe_verify_valid_cookie($action, $method); 

			 // get the list of logins 
		   	$o2g_url = ROXE_FQDN."/1.0/logins" ; 
		   	$rest_params = array();
		    $r= simple_rest_client($o2g_url, 'GET',$rest_params, [], $cookieText);
		    $rest_sequence= $r; 

		    if ($r['success']){

		    	foreach ( $r['response']['loginNames'] as $key => $login) {
		    		// if ($key==1) break; 
		    		$LOGINNAME = $login; 
		    		// get user details
				   	$o2g_url = ROXE_FQDN."/1.0/users"."/".$LOGINNAME ; 
				    $ru= simple_rest_client($o2g_url, 'GET',[], [], $cookieText);
				    $rest_sequence['response']['userdetails'][$key]=$ru['response'] ; 
		    	}
		    }
		   				 
		break; 

		// -- Get details of a user
		case "get_user_details" : 

			$cookieText= roxe_verify_valid_cookie($action, $method); 

    		$LOGINNAME = $_POST['loginName']; 
    		// get user details
		   	$o2g_url = ROXE_FQDN."/1.0/users"."/".$LOGINNAME ; 
		    $ru= simple_rest_client($o2g_url, 'GET',[], [], $cookieText);
		    $rest_sequence=$ru ; 

		break; 


		// -- Get user roting state
		case "get_user_routing" : 

			$cookieText= roxe_verify_valid_cookie($action, $method); 

    		$LOGINNAME = $_POST['loginName']; 
    		$rest_params = array(
		       'loginName' => $LOGINNAME,    
    		);
    		// get user details
		   	$o2g_url = ROXE_FQDN."/1.0/routing/state" ; 
		    $ru= simple_rest_client($o2g_url, 'GET',$rest_params, [], $cookieText);
		    $rest_sequence=$ru ; 
		 
		break; 


		// -- Get subscribr details
		case "get_subscriber_details" : 

			$cookieText= roxe_verify_valid_cookie($acsubscriberNumbertion, $method); 

			$subscriberNumber = $_POST['companyPhone']; 
			$NODEID = $_POST['nodeId'];

		    $o2g_url = ROXE_FQDN."/1.0/pbxs/".$NODEID."/instances/Subscriber/".$subscriberNumber ; 
		    $r= simple_rest_client($o2g_url, 'GET',[], [], $cookieText);
		    $rest_sequence= $r;

		    if ($r['success']) {

			    // convert attributes into name:value pairs 
			    $attrs = array(); 
			    foreach ($r['response']['attributes'] as $key2 => $attribute) {
			    	$x=''; $y=''; 
			    	foreach ($attribute as $keya => $valuea) {
			    		if ($keya=="name") $x=$valuea; 
			    		if ($keya=="value") $y=$valuea[0];
			    	}
			    	$attrs[$x] = $y; 
			    }
			    $rest_sequence['response']['attributes']=$attrs; 			    
		    }
		   				 
		break; 

		// -- Get model for a given OBJECTNAME 
		case "get_model" : 

			$cookieText= roxe_verify_valid_cookie($action, $method); 
			
    		// parameters 
    		$NODEID = $_POST['nodeId'];
    		
    		$OBJECTNAME = $_POST['objectName'];
    		if ($OBJECTNAME) $OBJECTNAME = '/'.$OBJECTNAME;


		    $o2g_url = ROXE_FQDN."/1.0/pbxs/".$NODEID."/model".$OBJECTNAME  ; 
		    $r= simple_rest_client($o2g_url, 'GET',[], [], $cookieText);
		    $rest_sequence=$r ;

		    // export format 
		    if (isset($_POST['format'])){
		    	if ($_POST['format']=="csv"){

		    		$data =  $r['response']['attributes']; // should be more than 162 lines. 

		    		// normalize content 		
		    		// usedWhen
		    		// lengthValue
		    		
		    		if ($OBJECTNAME=="/Subscriber"){
						foreach ($data as $key => $row) {
							if (!$row['lengthValue']) $data[$key]['lengthValue']='-'; 
							if (!$row['usedWhen']) $data[$key]['usedWhen']='-'; 
							if (is_array($row['allowedValues'])) $data[$key]['allowedValues'] = implode("|", $row['allowedValues']);
						} 
		    		}

		    		// simply export the datas in CSV
					$fp = fopen('php://output', 'w'); 
					if ($fp && $r['success']) 
					{     

						$firstLineFields=array_keys($data[0]); 
						$filename = $OBJECTNAME."_export.csv"; 
						$sep=";"; 
						header('Content-Type: text/csv');
						header('Content-Disposition: attachment; filename='.$filename);
						header('Pragma: no-cache');    
						header('Expires: 0');
						fputcsv($fp, $firstLineFields, $sep); 
						foreach ($data as $key => $row) {
							fputcsv($fp, array_values($row), $sep); 
						}      
						die; 
					} else die("error opening the stream or no data");
				

		    	}
		    }



		break; 



		case "put_subscriber" : 

			$cookieText= roxe_verify_valid_cookie($acsubscriberNumbertion, $method); 

			$subscriberNumber = $_POST['companyPhone']; 
			$NODEID = $_POST['nodeId'];


			$subscriberNumber= '19150'; 
			$NODEID= '407'; 					
			$subscriberFirstName = "patrice"; 
			$subscriberName="KOO-PHONERLITE";


			$rest_JSON_Params = '{"attributes": [{"name": "Annu_First_Name","value": ["' . $subscriberFirstName . '"]},{"name": "Annu_Name","value": ["' . $subscriberName . '"]}]}';                                                               
		   	$rest_params = json_decode($rest_JSON_Params); 

		    $o2g_url = ROXE_FQDN."/1.0/pbxs/".$NODEID."/instances/Subscriber/".$subscriberNumber ; 
		    $r= simple_rest_client($o2g_url, 'PUT',$rest_params, [], $cookieText);
		    $rest_sequence= $r;

		    if ($r['success']) {

			 			    
		    }
		   				 
		break; 




		case "make_call" : 

			$cookieText= roxe_verify_valid_cookie($acsubscriberNumbertion, $method); 

			$subscriberNumber = $_POST['companyPhone']; 
			$callee = $_POST['callee']; 

			$rest_JSON_Params = ' {"deviceId": "'.$subscriberNumber.'","callee": "'.$callee.'","autoAnswer": true}'; 
			// $rest_JSON_Params = ' {"deviceId": "'.$subscriberNumber.'","callee": "'.$callee.'"}';                                                               
                                                    
		   	$rest_params = json_decode($rest_JSON_Params); 

		    $o2g_url = ROXE_FQDN."/1.0/telephony/basicCall" ; 
		    $r= simple_rest_client($o2g_url, 'POST',$rest_params, [], $cookieText);
		    $rest_sequence= $r;

		    if ($r['success']) {

			 			    
		    }
		   				 
		break; 



		case "get_call" : 

			$cookieText= roxe_verify_valid_cookie($action, $method); 

		    $LOGINNAME = $_POST['loginName']; 

			// get phone status
    		$rest_params = array('loginName' => $LOGINNAME);
		   	$o2g_url = ROXE_FQDN."/1.0/telephony/calls" ; 
		    $ru= simple_rest_client($o2g_url, 'GET',$rest_params, [], $cookieText);
		    $rest_sequence=$ru ;

		break;


		case "basiccall_answer" : 

			$cookieText= roxe_verify_valid_cookie($action, $method); 

			$subscriberNumber = $_POST['companyPhone']; 
			$rest_JSON_Params = ' {"deviceId": "'.$subscriberNumber.'"}';                                                             
		   	$rest_params = json_decode($rest_JSON_Params); 

		    $o2g_url = ROXE_FQDN."/1.0/telephony/calls/".$callRef."/answer" ; 
		    $r= simple_rest_client($o2g_url, 'POST',$rest_params, [], $cookieText);
		    $rest_sequence= $r;

		    if ($r['success']) {

			 			    
		    }

		break;


		case "basiccall_drop" : 

			$cookieText= roxe_verify_valid_cookie($action, $method); 

			$LOGINNAME = $_POST['loginName'];  
			$rest_params = array('loginName' => $LOGINNAME);                                                             
		    $o2g_url = ROXE_FQDN."/1.0/telephony/calls/".$callRef."/dropme" ; 
		    $r= simple_rest_client($o2g_url, 'POST',$rest_params, [], $cookieText);
		    $rest_sequence= $r;

		    if ($r['success']) {

			 			    
		    }

		break;


		case "get_comlog" : 

			$cookieText= roxe_verify_valid_cookie($action, $method); 

    		$LOGINNAME = $_POST['loginName']; 

    		// parameters 
    		$rest_params = array('loginName' => $LOGINNAME); 
    		if (isset($_POST['unanswered'])) $rest_params['unanswered']='true';
    		if (isset($_POST['unacknowledged'])) $rest_params['unacknowledged']='true';

		    $r= simple_rest_client(ROXE_FQDN."/1.0/comlog/records/", 'GET',$rest_params, [], $cookieText);
		    $rest_sequence=$r ;
		   

		break; 


		case "delete_comlog" : 

			$cookieText= roxe_verify_valid_cookie($action, $method); 

			$LOGINNAME = $_POST['loginName'];
			$rest_params = array('loginName' => $LOGINNAME);  

    		$recordId = $_POST['recordId']; 
		    $r= simple_rest_client(ROXE_FQDN."/1.0/comlog/records/".$recordId, 'DELETE',$rest_params, [], $cookieText);
		    $rest_sequence=$r ;

		    
		break; 


		case "ack_comlog" : 

			$cookieText= roxe_verify_valid_cookie($action, $method); 

			$LOGINNAME = $_POST['loginName'];
			$queryParams =  "?loginName=$LOGINNAME&acknowledge=true"; 
			$rest_params = array('recordIds' => array($_POST['recordId']));
			
    		$recordId = $_POST['recordId']; 
		    $r= simple_rest_client(ROXE_FQDN."/1.0/comlog/records".$queryParams, 'PUT',$rest_params, [], $cookieText);
		    $rest_sequence=$r ;
		    
		break; 


		// routing forward to 
		case "forward_to" : 

			$cookieText= roxe_verify_valid_cookie($action, $method); 

			$LOGINNAME = $_POST['loginName'];
			$queryParams =  "?loginName=$LOGINNAME";   

			// POST parameters (in body)
			$rest_params_JSON =  '{ "forwardRoute": {"destinations": [ {"type": "VOICEMAIL"} ]} }'; 
			$rest_params=json_decode($rest_params_JSON);

			// make the query 
		    $o2g_url = ROXE_FQDN."/1.0/routing/forwardroute".$queryParams ; 
		    $r= simple_rest_client($o2g_url, 'POST',$rest_params, [], $cookieText);
		    $rest_sequence['routing/forwardroute[PUT']= $r;

		    if ($r['success']) {
			 			    
		    }


		break;


		// routing forward to 
		case "cancel_forward" : 

			$cookieText= roxe_verify_valid_cookie($action, $method); 

		    // delete a FORARD ROUTE
		    $LOGINNAME = $_POST['loginName'];
			$rest_params = array('loginName' => $LOGINNAME); 
		    $o2g_url = ROXE_FQDN."/1.0/routing/forwardroute" ; 
		    $r= simple_rest_client($o2g_url, 'DELETE',$rest_params, [], $cookieText);
		    $rest_sequence['routing/forwardroute[DELETE]'] = $r;  


		break;


		case "subscribe" : 


			$cookieText= roxe_verify_valid_cookie($acsubscriberNumbertion, $method); 

			// delete the existing subscription
			// $subId="***tests***"; 
			// $rest_params = array();
			// $o2g_url = ROXE_FQDN."/1.0/subscriptions/".$subId ; 
		 //    $r= simple_rest_client($o2g_url, 'DELETE',$rest_params, [], $cookieText);
		 //    $rest_sequence['delete']= $r;


			$rest_JSON_Params = ' { 
				"filter": { 
					"selectors": [ 
						{
					   "ids": [ "oxe19150","oxe19151","oxe19152","oxe19153"  ],
				       "names": [ "telephony" ]
				   		}
			    	]
			  		},
			  	"subscriptionId": "testpct4",
			  	"version":"1.0"
			 }';                                        
		   	$rest_params = json_decode($rest_JSON_Params); 
		    $r= simple_rest_client(ROXE_FQDN."/1.0/subscriptions", 'POST',$rest_params, [], $cookieText);
		    $rest_sequence= $r;

		    if ($r['success']) {

			 			    
		    }
		   				 
		break; 


		default:
			// -- step 1
		    $o2g_url = ROXE_FQDN."" ; 
		    $r= simple_rest_client($o2g_url, 'GET');
		    // var_dump($r); 
		    $rest_sequence[] = $r; 
		    break; 
   			
	}


    $json = array(
	  'version' => _VERSION
	  ,'success'=> true
	  ,'ret'=>$rest_sequence
	  ,'token'=> $TOKEN
	  ,'action'=>$action
	  ,'method'=>$method
	  ,'subscriberNumber'=>$subscriberNumber
	  ,'loginName'=>$LOGINNAME 
	  ,'nodeId'=>$NODEID 
	);


	// if (ENABLE_DEBUG_MODE){
	// 	$json['debug']=array(
	// 		'request'=>$_REQUEST 
	// 		,'debug_params'=>$debug_params
	// 		,'cookies'=>$_COOKIE
	// 	);
	// }
 //    encode_json_and_send_with_compression($json);



function roxe_verify_valid_cookie($action, $method){
	global $TOKEN;

	if (isset($_COOKIE['AlcUserId'])) {
		$TOKEN = $_COOKIE['AlcUserId']; 
		$cookieText='AlcUserId='. $TOKEN;
		return $cookieText;
	} else {
		$json = array(
				  'version' => _VERSION
				  ,'success'=> false
				  ,'action' =>$action
				  ,'method' =>$method
				  ,'status' =>"ERROR"
				  ,'message'=> "failed autentication - missing AlcUserId Cookie"
				);
		encode_json_and_send_with_compression($json);
		die(); 
	} 
	
}

function o2g_early_die($errorcode, $errormessage){

	$json = array(
	  'version' => _VERSION
	  ,'success'=> false
	  ,'errorid'=> $errorcode
	  ,'errormsg'=> $errormessage
	);
	encode_json_and_send_with_compression($json);
	die(); 
}


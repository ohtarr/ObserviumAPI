<?php

// do not authenticate requests to this call
//define("NO_AUTHENTICATION",1);
// always allow access from everywhere
header('Access-Control-Allow-Origin: *');
//header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Accept, Content-Type");
// never cache anything
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
// set our content type so the browser knows its json
header("Content-Type: application/json");
header("Accept: application/json");

include("../../includes/sql-config.inc.php");

include_once($config['html_dir'] . "/includes/functions.inc.php");

function microtimeTicks(){
        $ticks = explode(' ', microtime());
        // Return the sum of the two numbers (double precision number)
        return $ticks[0] + $ticks[1];
}

function get_devices(){
	$query = "SELECT * FROM `devices` ";
//	$query .= $where . $query_permitted . $sort;
	$results = dbFetchRows($query);
	foreach ($results as $key => $device){
		$array[$device[device_id]] = $device;
	}
	ksort($array);
	//$object = (object) $array;

	return $array;
}

$start = microtimeTicks();       // get the current microtime for performance tracking

/*
if( !isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != 'on')    {
        header("HTTP/1.1 301 Moved Permanently");                       // Enforce HTTPS for all traffic
        header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
        exit(); 
}

/**/

$RESPONSE = [];

/*
if ($_SERVER['REQUEST_METHOD'] != 'POST') {             // Handle non-post requests as an error
    $RESPONSE['success']= false;
    $RESPONSE['message']  = "Request method not supported";
        $end = microtimeTicks();         // get the current microtime for performance tracking
        $RESPONSE['time'] = $end - $start;                      // calculate the total time we executed
    exit(json_encode($RESPONSE));
}
/**/
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
	$devices = get_devices();
	if ($_GET[hostname])
	{
		foreach($devices as $id => $device){
			if ($_GET[hostname] == $device[hostname]){

				$RESPONSE['success'] = true;
				$RESPONSE['data'] = $device;
				$RESPONSE['message']      = "Device ID " . $id . " named " . $device[hostname] . " returned!";
				$end = microtimeTicks();         // get the current microtime for performance tracking
				$RESPONSE['time'] = $end - $start;                      // calculate the total time we executed
				exit(json_encode($RESPONSE));
			}
		}
		$RESPONSE['success'] = false;
		$RESPONSE['message']      = "Device " . $_GET[hostname] . " not found!";
		$end = microtimeTicks();         // get the current microtime for performance tracking
		$RESPONSE['time'] = $end - $start;                      // calculate the total time we executed
		exit(json_encode($RESPONSE));

	} elseif ($_GET[id])
	{
		if($devices[$_GET[id]]){
			$RESPONSE['success'] = true;
			$RESPONSE['data'] = $devices[$_GET[id]];
			$RESPONSE['message']      = "Device ID " . $devices[$_GET[id]][device_id] . " named " . $devices[$_GET[id]][hostname] . " returned!";
			$end = microtimeTicks();         // get the current microtime for performance tracking
			$RESPONSE['time'] = $end - $start;                      // calculate the total time we executed
			exit(json_encode($RESPONSE));

		} else {
			$RESPONSE['success'] = false;
			$RESPONSE['message']      = "Device ID " . $_GET[id] . " not found!";
			$end = microtimeTicks();         // get the current microtime for performance tracking
			$RESPONSE['time'] = $end - $start;                      // calculate the total time we executed
			exit(json_encode($RESPONSE));
		}
	} else {
		$RESPONSE['success'] = true;
		$RESPONSE['data'] = $devices;
		$RESPONSE['message']      = "All devices returned!";
		$end = microtimeTicks();         // get the current microtime for performance tracking
		$RESPONSE['time'] = $end - $start;                      // calculate the total time we executed
		exit(json_encode($RESPONSE));
	}

}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$POSTED = (array) json_decode(file_get_contents("php://input"));

	if ($POSTED["debug"] == 1){
		$RESPONSE['debug']['POST_params'] = $POSTED;
	}

	if (empty($POSTED)){
			$RESPONSE['success']= false;
			$RESPONSE['message']  = "No POST data found!";
			$end = microtimeTicks();         // get the current microtime for performance tracking
			$RESPONSE['time'] = $end - $start;                      // calculate the total time we executed
			exit(json_encode($RESPONSE));
	}

	//Required Parameters

	if (empty($POSTED["action"])) {
		$RESPONSE['success']= false;
		$RESPONSE['message']      = "Missing or empty parameter ->{action}<-";
		$end = microtimeTicks();         // get the current microtime for performance tracking
		$RESPONSE['time'] = $end - $start;                      // calculate the total time we executed
		exit(json_encode($RESPONSE));
	}

	if ($POSTED["action"] == "add_device") {
		$params = ["hostname"];

		foreach ($params as $param){
				if ( empty($POSTED[$param])) {  // Handle missing actions as an error
						$RESPONSE['success']= false;
						$RESPONSE['message']      = "Missing or empty parameter ->{$param}<-";
						$end = microtimeTicks();         // get the current microtime for performance tracking
						$RESPONSE['time'] = $end - $start;                      // calculate the total time we executed
						exit(json_encode($RESPONSE));
				}
		}

		try{

			$device_id = add_device($POSTED["hostname"]);

			if ($POSTED['debug'] == 1){
				$RESPONSE['debug']['api_return'] = $device_id;
			}

			if ($device_id == false){
				$RESPONSE['success'] = false;
				$RESPONSE['message'] = $POSTED['action'] . " failed to return valid device ID.";
			} else {
				$RESPONSE['success'] = true;
				$RESPONSE['message'] = $POSTED['action'] . " returned valid device ID: " . $device_id;
	//			shell_exec('../../poller.php -h ' . $POSTED["hostname"] . ' >> /dev/null &');
				shell_exec('../../discovery.php -h ' . $POSTED["hostname"] . ' >> /dev/null &');
			}

			$end = microtimeTicks();         // get the current microtime for performance tracking
			$RESPONSE['time'] = $end - $start;                      // calculate the total time we executed
			exit(json_encode($RESPONSE));                           // terminate and respond with json

		}catch (\Exception $e) {
				// catch exceptions as BAD data
				$RESPONSE['success'] = false;
				$RESPONSE['message'] = "Caught exception {$e->getMessage()}\n";
				$end = microtimeTicks();         						// get the current microtime for performance tracking
				$RESPONSE['time'] = $end - $start;                      // calculate the total time we executed
				exit(json_encode($RESPONSE));                           // terminate and respond with json
		}



	} elseif ($POSTED["action"] == "delete_device") {
		$params = ["hostname"];

		foreach ($params as $param){
				if ( empty($POSTED[$param])) {  // Handle missing actions as an error
						$RESPONSE['success']= false;
						$RESPONSE['message']      = "Missing or empty parameter ->{$param}<-";
						$end = microtimeTicks();         // get the current microtime for performance tracking
						$RESPONSE['time'] = $end - $start;                      // calculate the total time we executed
						exit(json_encode($RESPONSE));
				}
		}

		try{

			$device_id = get_device_id_by_hostname($POSTED["hostname"]);

			if ($device_id){
				$delete = delete_device($device_id, true);

				if ($POSTED['debug'] == 1){
					$RESPONSE['debug']['api_return'] = $delete;
				}
				
				if ($delete){
					$RESPONSE['success'] = true;				
					$RESPONSE['message'] = $POSTED['action'] . " successfully deleted device id: " . $device_id;

				} else {
					$RESPONSE['success'] = false;				
					$RESPONSE['message'] = $POSTED['action'] . " failed to delete device id: " . $device_id;
				}
				$end = microtimeTicks();         // get the current microtime for performance tracking
				$RESPONSE['time'] = $end - $start;                      // calculate the total time we executed
				exit(json_encode($RESPONSE));                           // terminate and respond with json

			} else {
				$RESPONSE['success'] = false;
				$RESPONSE['message'] = $POSTED['action'] . " returned no valid device ID.";
				if ($POSTED['debug'] == 1){
					$RESPONSE['debug']['api_return'] = $device_id;
				}
				$end = microtimeTicks();         // get the current microtime for performance tracking
				$RESPONSE['time'] = $end - $start;                      // calculate the total time we executed
				exit(json_encode($RESPONSE));                           // terminate and respond with json
			}

		}catch (\Exception $e) {
				// catch exceptions as BAD data
				$RESPONSE['success'] = false;
				$RESPONSE['message'] = "Caught exception {$e->getMessage()}\n";
				$end = microtimeTicks();         						// get the current microtime for performance tracking
				$RESPONSE['time'] = $end - $start;                      // calculate the total time we executed
		}

	} elseif ($POSTED['action'] == poll_device){
		$params = ["hostname"];

		foreach ($params as $param){
				if ( empty($POSTED[$param])) {  // Handle missing actions as an error
						$RESPONSE['success']= false;
						$RESPONSE['message']      = "Missing or empty parameter ->{$param}<-";
						$end = microtimeTicks();         // get the current microtime for performance tracking
						$RESPONSE['time'] = $end - $start;                      // calculate the total time we executed
						exit(json_encode($RESPONSE));
				}
		}

		try{

			$hostname = $POSTED["hostname"];
	//		$poll = exec('../../poller.php -h ' . $hostname);
			$poll = "test";
			shell_exec('../../poller.php -h ' . $hostname . ' >> /dev/null &');

	//		if ($POSTED['debug'] == 1){
	//			$RESPONSE['debug']['api_return'] = $poll;
	//		}
			
	//		if ($poll){
				$RESPONSE['success'] = true;				
				$RESPONSE['message'] = $POSTED['action'] . " queued device for polling : " . $hostname;

	//		} else {
	//			$RESPONSE['success'] = false;				
	//			$RESPONSE['message'] = $POSTED['action'] . " failed to poll device : " . $hostname;
	//		}
			$end = microtimeTicks();         // get the current microtime for performance tracking
			$RESPONSE['time'] = $end - $start;                      // calculate the total time we executed
			exit(json_encode($RESPONSE));                           // terminate and respond with json

		}catch (\Exception $e) {
				// catch exceptions as BAD data
				$RESPONSE['success'] = false;
				$RESPONSE['message'] = "Caught exception {$e->getMessage()}\n";
				$end = microtimeTicks();         						// get the current microtime for performance tracking
				$RESPONSE['time'] = $end - $start;                      // calculate the total time we executed
		}
	} elseif ($POSTED['action'] == poll_all_devices){
		$params = [];

		foreach ($params as $param){
				if ( empty($POSTED[$param])) {  // Handle missing actions as an error
						$RESPONSE['success']= false;
						$RESPONSE['message']      = "Missing or empty parameter ->{$param}<-";
						$end = microtimeTicks();         // get the current microtime for performance tracking
						$RESPONSE['time'] = $end - $start;                      // calculate the total time we executed
						exit(json_encode($RESPONSE));
				}
		}

		try{
			shell_exec('../../poller.php -h all >> /dev/null &');

			$RESPONSE['success'] = true;				
			$RESPONSE['message'] = $POSTED['action'] . " queued all devices for polling.";
			$end = microtimeTicks();         // get the current microtime for performance tracking
			$RESPONSE['time'] = $end - $start;                      // calculate the total time we executed
			exit(json_encode($RESPONSE));                           // terminate and respond with json

		}catch (\Exception $e) {
				// catch exceptions as BAD data
				$RESPONSE['success'] = false;
				$RESPONSE['message'] = "Caught exception {$e->getMessage()}\n";
				$end = microtimeTicks();         						// get the current microtime for performance tracking
				$RESPONSE['time'] = $end - $start;                      // calculate the total time we executed
		}
	} else {
		$RESPONSE['success']= false;
		$RESPONSE['message']      = "Unsupported Action!";
		$end = microtimeTicks();         // get the current microtime for performance tracking
		$RESPONSE['time'] = $end - $start;                      // calculate the total time we executed
		exit(json_encode($RESPONSE));
	}
}

//exit(json_encode($RESPONSE));                           // terminate and respond with json

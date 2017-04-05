<?php
//Custom API to interface to Observium
//Observium functions used :
//dbFetchRows
//set_entity_attrib
//


// Get provided credentials
if(!isset($_SERVER['PHP_AUTH_USER'])) { $user = ''; }else{ $user = $_SERVER['PHP_AUTH_USER']; }
if(!isset($_SERVER['PHP_AUTH_PW'  ])) { $pass = ''; }else{ $pass = $_SERVER['PHP_AUTH_PW'  ]; }

require_once "creds.php";

// check if they are a valid user
$valid = (in_array($user, array_keys($users))) && (md5($pass) == $users[$user]["hash"]);

// if credentials are NOT valid, reject the user and prompt for http basic auth
if (!$valid) {
    header('WWW-Authenticate: Basic realm="Agar"');
    header('HTTP/1.0 401 Unauthorized');
    die ("Authentication failed");
}


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

include("/opt/observium/includes/sql-config.inc.php");

include_once("/opt/observium/html/includes/functions.inc.php");

function microtimeTicks(){
		$ticks = explode(' ', microtime());
		// Return the sum of the two numbers (double precision number)
		return $ticks[0] + $ticks[1];
}

function quitApi($RESPONSE){
	global $start;
	$end = microtimeTicks();		 // get the current microtime for performance tracking
	$RESPONSE['time'] = $end - $start;					  // calculate the total time we executed
	exit(json_encode($RESPONSE));
}

function get_devices(){
		$query = "SELECT * FROM `devices` ";
		$results = dbFetchRows($query);

		foreach ($results as $key => $device){
				$array[$device[device_id]] = $device;
		}
		ksort($array);
	return $array;
}

function get_device($deviceid){
	$query = "SELECT * FROM `devices` WHERE device_id = " . $deviceid;
	$results = dbFetchRows($query);
	return $results;
}

function get_groups(){
	$results = dbFetchRows("SELECT * FROM `groups` ORDER BY `group_name`");
	foreach($results as $key => $group){
		$array[$group['group_id']] = $group;
	}
	ksort($array);
	return $array;
}

$start = microtimeTicks();	   // get the current microtime for performance tracking

/*
if( !isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != 'on')	{
		header("HTTP/1.1 301 Moved Permanently");					   // Enforce HTTPS for all traffic
		header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
		exit(); 
}

/**/

$RESPONSE = [];

/*
if ($_SERVER['REQUEST_METHOD'] != 'POST') {			 // Handle non-post requests as an error
	$RESPONSE['success']= false;
	$RESPONSE['message']  = "Request method not supported";
		$end = microtimeTicks();		 // get the current microtime for performance tracking
		$RESPONSE['time'] = $end - $start;					  // calculate the total time we executed
	exit(json_encode($RESPONSE));
}
/**/
//quitApi($_SERVER);
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
	if($_GET['type'] == "device"){
		$devices = get_devices();
		if ($_GET['hostname'])
		{
			foreach($devices as $id => $device){
				if ($_GET['hostname'] == $device['hostname']){
					$RESPONSE['success'] = true;
					$RESPONSE['data'] = $device;
					$RESPONSE['message']	  = "Device ID " . $id . " named " . $device[hostname] . " returned!";
					quitApi($RESPONSE);
				}
			}
			$RESPONSE['success'] = false;
			$RESPONSE['message']	  = "Device " . $_GET[hostname] . " not found!";
			quitApi($RESPONSE);
		} elseif ($_GET['id'])
		{
			if($devices[$_GET['id']]){
				$RESPONSE['success'] = true;
				$RESPONSE['data'] = $devices[$_GET['id']];
				$RESPONSE['message']	  = "Device ID " . $devices[$_GET['id']]['device_id'] . " named " . $devices[$_GET['id']]['hostname'] . " returned!";
				quitApi($RESPONSE);
			} else {
				$RESPONSE['success'] = false;
				$RESPONSE['message']	  = "Device ID " . $_GET['id'] . " not found!";
				quitApi($RESPONSE);
			}
		} else {
			$RESPONSE['success'] = true;
			$RESPONSE['data'] = $devices;
			$RESPONSE['message']	  = "All " . count($devices) . " devices returned!";
			quitApi($RESPONSE);
		}
	} elseif($_GET['type'] == "group"){

		$agroups = get_groups();
//		$agroups = dbFetchRows("SELECT * FROM `groups` ORDER BY `group_name`");
				$RESPONSE['success']	= true;
		$RESPONSE['data']	= $agroups;
				$RESPONSE['message']	= "All " . count($agroups) . " groups returned!";
				quitApi($RESPONSE);
	} else {
		$RESPONSE['success'] = false;
				$RESPONSE['message']	  = "Invalid type -> {$_GET['type']} <- requested!";
				quitApi($RESPONSE);
	}
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$POSTED = json_decode(file_get_contents("php://input"), true);
	//$RESPONSE['POSTED'] = $POSTED;

	if ($POSTED["debug"] == 1){
		$RESPONSE['debug']['post_params'] = $POSTED;
	}

	if (empty($POSTED)){
			$RESPONSE['success']= false;
			$RESPONSE['message']  = "No POST data found!";
			quitApi($RESPONSE);
	}

	//Required Parameters

	if (empty($POSTED["action"])) {
		$RESPONSE['success']= false;
		$RESPONSE['message']	  = "Missing or empty parameter ->{action}<-";
		quitApi($RESPONSE);
	}

	if ($POSTED["action"] == "add_device") {
		$params = ["hostname"];
		foreach ($params as $param){
				if ( !isset($POSTED[$param])) {  // Handle missing actions as an error
						$RESPONSE['success']= false;
						$RESPONSE['message']	  = "Missing or empty parameter ->{$param}<-";
						quitApi($RESPONSE);
				}
		}
		if(!checkdnsrr($POSTED['hostname'])){
			$RESPONSE['success'] = false;
			$RESPONSE['message'] = "Hostname {$POSTED['hostname']} failed to resolve.";
			quitApi($RESPONSE);
		}
		if (get_device_id_by_hostname($POSTED['hostname'])){
			if($POSTED['debug']=1){
				$RESPONSE['debug']['obs_raw']=get_device_id_by_hostname($POSTED['hostname']);
			}
						$RESPONSE['success'] = false;
						$RESPONSE['message'] = "Hostname {$POSTED['hostname']} already exists!";
						quitApi($RESPONSE);
		}


		try{
			$hostname = $POSTED["hostname"];
			ob_start();
			$device_id = add_device($hostname);
			ob_end_clean();
			if ($POSTED['debug'] == 1){
				$RESPONSE['debug']['obs_raw'] = $device_id;
			}

			if ($device_id == false){
				$RESPONSE['success'] = false;
				$RESPONSE['message'] = $POSTED['action'] . " failed for device " . $POSTED['hostname'] . ", no valid device ID returned.";
			} else {
				$RESPONSE['success'] = true;
				$device = get_device($device_id);
				$RESPONSE['data']= $device[0];

				$RESPONSE['message'] = $POSTED['action'] . " returned valid device ID: " . $device_id . " for device " . $POSTED['hostname'] . ".";

			}
			quitApi($RESPONSE);
		}catch (\Exception $e) {
				// catch exceptions as BAD data
				$RESPONSE['success'] = false;
				$RESPONSE['message'] = "Caught exception {$e->getMessage()}\n";
				quitApi($RESPONSE);
		}

	} elseif ($POSTED["action"] == "delete_device") {

		if ($POSTED['id']) {
			$device_id = $POSTED['id'];
		} elseif ($POSTED['hostname']) {
			$device_id = get_device_id_by_hostname($POSTED['hostname']);
		} else {
			$RESPONSE['success'] = false;
						$RESPONSE['message'] = "Failed to delete device due to missing ID or HOSTNAME";
			quitApi($RESPONSE);
		}
		if ($device_id){
			try{
				//$device_id = get_device_id_by_hostname($POSTED["hostname"]);
				//$delete = delete_device($device_id, true);
				$delete = delete_device($device_id);

				if ($delete){
					if ($POSTED['debug']=1){
						$RESPONSE['debug']['obs_raw'] = $delete;
					}
										$RESPONSE['data']['device_id'] = $device_id;

					if (preg_match("/\* Deleted device:/", $delete, $hits)){
						$RESPONSE['success'] = true;
												$RESPONSE['message'] = $POSTED['action'] . " successfully deleted device id: " . $device_id;
					} elseif ($delete == "\nError finding host in the database."){
						$RESPONSE['success'] = false;
						$RESPONSE['message'] = $POSTED['action'] . " failed to find device id: " . $device_id;
					} else {
											$RESPONSE['success'] = false;
											$RESPONSE['message'] = $POSTED['action'] . " failed to delete device id: " . $device_id;
					}
				} else {
					$RESPONSE['success'] = false;
					$RESPONSE['message'] = $POSTED['action'] . " failed to delete device id: " . $device_id;
					$RESPONSE['data']['device_id'] = $device_id;
				}
				quitApi($RESPONSE);
			}catch (\Exception $e) {
				// catch exceptions as BAD data
				$RESPONSE['success'] = false;
				$RESPONSE['message'] = "Caught exception {$e->getMessage()}\n";
				$RESPONSE['data']['device_id'] = $device_id;
				quitApi($RESPONSE);
			}
		} else {
			$RESPONSE['success'] = false;
			$RESPONSE['message'] = $POSTED['action'] . " returned no valid device ID.";
			if ($POSTED['debug'] == 1){
				$RESPONSE['debug']['obs_raw'] = $device_id;
			}
			quitApi($RESPONSE);
		}

/*
	} elseif ($POSTED["action"] == "modify_device") {
		$params = ["id","option"];
		$subparams = ["disable_port_discovery","disable_port_polling"];

		foreach ($params as $param){
			if ( !isset($POSTED[$param])) {  // Handle missing actions as an error
				$RESPONSE['success']= false;
				$RESPONSE['message']	  = "Missing or empty parameter ->{$param}<-";
				quitApi($RESPONSE);
			}
		}
		try{
			switch ($POSTED['option']) {
				case "disable_port_discovery":
					set_entity_attrib("device", $POSTED['id'], "discover_ports", 0);
					$RESPONSE['success'] = true;
					$RESPONSE['message'] = $POSTED['action'] . " successfully modified " . $POSTED['option'] . " on device id: " . $POSTED['id'];
					quitApi($RESPONSE);
				break;
				case "disable_port_polling":
					set_entity_attrib("device", $POSTED['id'], "poll_ports", 0);
					$RESPONSE['success'] = true;
					$RESPONSE['message'] = $POSTED['action'] . " successfully modified " . $POSTED['option'] . " on device id: " . $POSTED['id'];
					quitApi($RESPONSE);
				break;
				default:
					$RESPONSE['success']= false;
					$RESPONSE['message']	  = "Invalid option ->{$POSTED['option']}<-";
					quitApi($RESPONSE);
				break;
			}
		}catch (\Exception $e) {
				// catch exceptions as BAD data
				$RESPONSE['success'] = false;
				$RESPONSE['message'] = "Caught exception {$e->getMessage()}\n";
				quitApi($RESPONSE);
		}

/**/
	} elseif ($POSTED["action"] == "set_entity_attrib") {
		$params = ["type","id","option", "value"];

		foreach ($params as $param){
			if ( !isset($POSTED[$param])) {  // Handle missing actions as an error
				$RESPONSE['success']= false;
				$RESPONSE['message']	  = "Missing or empty parameter ->{$param}<-";
				quitApi($RESPONSE);
			}
		}
		try{
			set_entity_attrib($POSTED['type'], $POSTED['id'], $POSTED['option'], $POSTED['value']);
			$RESPONSE['success'] = true;
			$RESPONSE['message'] = $POSTED['action'] . " successfully modified " . $POSTED['option'] . " on device id: " . $POSTED['id'];
			quitApi($RESPONSE);
		}catch (\Exception $e) {
			// catch exceptions as BAD data
			$RESPONSE['success'] = false;
			$RESPONSE['message'] = "Caught exception {$e->getMessage()}\n";
			quitApi($RESPONSE);
		}
	} elseif ($POSTED["action"] == "add_group") {
		$params = ["group_type", "name", "description", "device_association", "entity_association"];

		foreach ($params as $param){
			if ( !isset($POSTED[$param])) {  // Handle missing actions as an error
				$RESPONSE['success']= false;
				$RESPONSE['message']	  = "Missing or empty parameter ->{$param}<-";
				quitApi($RESPONSE);
			}
		}
		$vars = array(
			'entity_type'			=>	$POSTED['group_type'],
			'group_name'			=>	$POSTED['name'],
			'group_descr'			=>	$POSTED['description'],
			'assoc_device_conditions'	=>	$POSTED['device_association'],
			'assoc_entity_conditions'	=>	$POSTED['entity_association'],
		);

		$ok = TRUE;
		foreach (array('entity_type', 'group_name', 'group_descr', 'assoc_device_conditions', 'assoc_entity_conditions') as $var)
		{
			if (!isset($vars[$var]) || strlen($vars[$var]) == '0') { $ok = FALSE; }
		}
		if ($ok)
		{
			try{
				$group_array = array(
					'entity_type'	=>	$vars['entity_type'],
					'group_name'	=>	$vars['group_name'],
					'group_descr'	=>	$vars['group_descr'],
				);

				$vars['group_id'] = dbInsert('groups', $group_array);
				if (is_numeric($vars['group_id']))
				{
					$dev_conds = array();
					foreach (explode("\n", $vars['assoc_device_conditions']) AS $cond)
					{
						list($this['attrib'], $this['condition'], $this['value']) = explode(" ", trim($cond), 3);
						$dev_conds[] = $this;
					}

					if ($vars['assoc_device_conditions'] == "*") { $vars['assoc_device_conditions'] = json_encode(array()); }

					$ent_conds = array();
					foreach (explode("\n", $vars['assoc_entity_conditions']) AS $cond)
					{
						list($this['attrib'], $this['condition'], $this['value']) = explode(" ", trim($cond), 3);
						$ent_conds[] = $this;
					}

					if ($vars['assoc_entity_conditions'] == "*") { $vars['assoc_entity_conditions'] = json_encode(array()); }

					$assoc_array = array(
						'group_id'			=>	$vars['group_id'],
						'entity_type'		=>	$vars['entity_type'],
						'device_attribs'	=>	json_encode($dev_conds),
						'entity_attribs'	=>	json_encode($ent_conds),
					);

					$vars['assoc_id'] = dbInsert('groups_assoc', $assoc_array);


					if (is_numeric($vars['assoc_id']))
					{
//TEMP REMOVE THIS, it is LOCKING up!
						foreach (dbFetchRows("SELECT * FROM `devices`") as $udevice)
						{
							ob_start();
							update_device_group_table($udevice);
							ob_end_clean();
						}
/**/
						$RESPONSE['success'] = true;
						$RESPONSE['data']['group_id'] = $vars['group_id'];
						$RESPONSE['data']['assoc_id'] = $vars['assoc_id'];
						$RESPONSE['message'] = "Group " . $vars['group_name'] . " with ID " . $vars['group_id'] . " created with assoc ID " . $vars['assoc_id'];
						quitApi($RESPONSE);
					} else {
						$RESPONSE['success'] = false;
						$RESPONSE['data']['group_id'] = $vars['group_id'];
						$RESPONSE['message'] = "Group " . $vars['group_name'] . " with ID " . $vars['group_id'] . " failed to associate, group deleted.";
						quitApi($RESPONSE);
						dbDelete('groups', "`group_id` = ?", array($vars['group_id'])); // Undo group create
					}
				} else {
					$RESPONSE['success'] = false;
					$RESPONSE['message'] = "Failed to create device group for unknown reasons!";
					quitApi($RESPONSE);
				}
			}catch (\Exception $e) {
				// catch exceptions as BAD data
				$RESPONSE['success'] = false;
				$RESPONSE['message'] = "Caught exception {$e->getMessage()}\n";
				quitApi($RESPONSE);
			}
		}
	} elseif ($POSTED["action"] == "delete_group") {
		$params = ["name"];

		foreach ($params as $param){
			if ( !isset($POSTED[$param])) {  // Handle missing actions as an error
				$RESPONSE['success']= false;
				$RESPONSE['message']	  = "Missing or empty parameter ->{$param}<-";
				quitApi($RESPONSE);
			}
		}
		//$RESPONSE['data'] = dbFetchRows("SELECT * FROM `groups`");
		//quitApi($RESPONSE);
		foreach (dbFetchRows("SELECT * FROM `groups`") as $ugroup)
		{
			if ($ugroup['group_name'] == $POSTED['name']){
				$group_id = $ugroup['group_id'];
				$RESPONSE['data']['group_id'] = $group_id;
				//break;
			}
		}
		if ($group_id){

			dbDelete('groups',	   '`group_id` = ?', array($group_id));
			dbDelete('group_table',  '`group_id` = ?', array($group_id));
			dbDelete('groups_assoc', '`group_id` = ?', array($group_id));

			$groupexists = dbFetchRows("SELECT * FROM `groups` WHERE `group_id` = " . $group_id);

			if (!$groupexists){
				$RESPONSE['success'] = true;
				$RESPONSE['message'] = "Group -> " . $POSTED['name'] . " <- was successfully deleted!";
				quitApi($RESPONSE);
			} else {
				$RESPONSE['success'] = false;
				$RESPONSE['message'] = "Group -> " . $POSTED['name'] . " <- failed to delete!";
				$RESPONSE['data'] = $groupexists;
				quitApi($RESPONSE);
			}
		} else {
			$RESPONSE['success'] = false;
			$RESPONSE['message'] = "Group -> " . $POSTED['name'] . " <- Does not exist!";
			quitApi($RESPONSE);
		}
	} elseif ($POSTED["action"] == "dbquery") {
		$params = ["table","key","id"];
		
		foreach ($params as $param){
			if ( !isset($POSTED[$param])) {  // Handle missing actions as an error
				$RESPONSE['success']= false;
				$RESPONSE['message']	  = "Missing or empty parameter ->{$param}<-";
				quitApi($RESPONSE);
			}
		}
		$sql = "SELECT * FROM " . $POSTED['table'] . " WHERE " . $POSTED['key'] . " LIKE ?";
		$sqlparams[] = $POSTED['id'];
		$result = dbFetchRows($sql,$sqlparams);

		if ($result){
			$RESPONSE['success'] = true;
			$RESPONSE['message'] = "dbquery was successful!";
			$RESPONSE['data'] = $result;
			quitApi($RESPONSE);
		} else {
			$RESPONSE['success'] = false;
			$RESPONSE['message'] = "dbquery was NOT successful!";
			quitApi($RESPONSE);
		}

	} elseif ($POSTED["action"] == "dbupdate") {	
		$params = ["table","key","id","params"];

		foreach ($params as $param){
			if ( !isset($POSTED[$param])) {  // Handle missing actions as an error
				$RESPONSE['success']= false;
				$RESPONSE['message']	  = "Missing or empty parameter ->{$param}<-";
				quitApi($RESPONSE);
			}
		}

		if (is_array($POSTED['params']))
		{
			$result = dbUpdate($POSTED['params'], $POSTED['table'], $POSTED['key'] . " = ?", array($POSTED['id']));
		}
		if ($result){
			$RESPONSE['success'] = true;
			$RESPONSE['message'] = "dbupdate was successful!";
			$RESPONSE['data'] = dbFetchRows("SELECT * FROM " . $POSTED['table'] . " WHERE " . $POSTED['key'] . " LIKE ?", array($POSTED['id']));
			quitApi($RESPONSE);
		} else {
			$RESPONSE['success'] = false;
			$RESPONSE['message'] = "dbupdate was NOT successful!";
			$RESPONSE['data'] = dbFetchRows("SELECT * FROM " . $POSTED['table'] . " WHERE " . $POSTED['key'] . " LIKE ?", array($POSTED['id']));
			quitApi($RESPONSE);
		}
		
	} else {
		$RESPONSE['success']= false;
		$RESPONSE['message']	  = "Unsupported Action!";
		quitApi($RESPONSE);
	}
}

//exit(json_encode($RESPONSE));						   // terminate and respond with json

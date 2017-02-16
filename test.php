<?php

//include main include file
include("/opt/observium/includes/sql-config.inc.php");
//include_once("/opt/observium/html/includes/functions.inc.php");

//Custom function to get all devices from Observium
function get_devices(){
	$query = "SELECT * FROM `devices` ";
	$results = dbFetchRows($query);

	foreach ($results as $key => $device){
			$array[$device[device_id]] = $device;
	}
	ksort($array);
	return $array;
}

//Custom function to get a single device from Observium
function get_device($deviceid){
	$query = "SELECT * FROM `devices` WHERE device_id = " . $deviceid;
	$results = dbFetchRows($query);
	return $results;
}

//Custom function to get all groups from Observium
function get_groups(){
	$results = dbFetchRows("SELECT * FROM `groups` ORDER BY `group_name`");
	foreach($results as $key => $group){
		$array[$group['group_id']] = $group;
	}
	ksort($array);
	return $array;
}

//Observium function to return ID of a device from it's hostname.
//get_device_id_by_hostname($POSTED['hostname']

//Observium function to add a device to Observium using it's hostname.  Returns some html, ob_start/end suppresses this returned data.
//ob_start();
//$device_id = add_device($hostname);
//ob_end_clean();

//Observium function to delete a device using it's device ID.
//$delete = delete_device($device_id);

/*
//ADDING GROUPS!
//Observium functions to add a group to Observium
$vars = array(
		'entity_type'                   =>      $POSTED['group_type'],
		'group_name'                    =>      $POSTED['name'],
		'group_descr'                   =>      $POSTED['description'],
		'assoc_device_conditions'       =>      $POSTED['device_association'],
		'assoc_entity_conditions'       =>      $POSTED['entity_association'],
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
			'entity_type'   =>      $vars['entity_type'],
			'group_name'    =>      $vars['group_name'],
			'group_descr'   =>      $vars['group_descr'],
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
				'group_id'                      =>      $vars['group_id'],
				'entity_type'           =>      $vars['entity_type'],
				'device_attribs'        =>      json_encode($dev_conds),
				'entity_attribs'        =>      json_encode($ent_conds),
			);

			$vars['assoc_id'] = dbInsert('groups_assoc', $assoc_array);


			if (is_numeric($vars['assoc_id']))
			{
				foreach (dbFetchRows("SELECT * FROM `devices`") as $udevice)
				{
					ob_start();
					update_device_group_table($udevice);
					ob_end_clean();
				}
				$RESPONSE['success'] = true;
				$RESPONSE['data']['group_id'] = $vars['group_id'];
				$RESPONSE['data']['assoc_id'] = $vars['assoc_id'];
				$RESPONSE['message'] = "Group " . $vars['group_name'] . " with ID " . $vars['group_id'] . " created with assoc ID " . $vars['assoc_id'$
				quitApi($RESPONSE);
/**/

/*
//DELETE GROUPS
//Observium functions to delete a group from Observium
foreach (dbFetchRows("SELECT * FROM `groups`") as $ugroup)
{
	if ($ugroup['group_name'] == $POSTED['name']){
		$group_id = $ugroup['group_id'];
		$RESPONSE['data']['group_id'] = $group_id;
		//break;
	}
}
if ($group_id){

	dbDelete('groups',       '`group_id` = ?', array($group_id));
	dbDelete('group_table',  '`group_id` = ?', array($group_id));
	dbDelete('groups_assoc', '`group_id` = ?', array($group_id));

	$groupexists = dbFetchRows("SELECT * FROM `groups` WHERE `group_id` = " . $group_id);

	if (!$groupexists){
		$RESPONSE['success'] = true;
		$RESPONSE['message'] = "Group -> " . $POSTED['name'] . " <- was successfully deleted!";
		quitApi($RESPONSE);


/**/







//Observium function to get all device groups
//print_r($groups = get_type_groups('device'));



//set custom entity attributes:

//enable/disable custom snmp location
set_entity_attrib('device', "3171", 'override_sysLocation_bool', '0');

//configure a custom snmp location
//set_entity_attrib('device', "3171", 'override_sysLocation_string', 'test4');

//disable port DISCOVERY on a device
//set_entity_attrib("device", $POSTED['id'], "discover_ports", 0);

//disable port POLLING on a device
//set_entity_attrib("device", $POSTED['id'], "poll_ports", 0);



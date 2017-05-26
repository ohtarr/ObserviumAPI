#!/usr/bin/env php
<?php
include("/opt/observium/includes/sql-config.inc.php");
include("/opt/observium/includes/discovery/functions.inc.php");

$todiscover = dbFetchRows("SELECT * FROM devices WHERE status = 1 AND disabled = 0 AND (last_discovered IS NULL OR last_discovered = '0000-00-00 00:00:00') ORDER BY last_discovered_timetaken ASC");

if(!$todiscover)
{
	exit("No new devices to discover!\n");
}

$portdisableparams = [
	"ignore"        =>      1,
	"disabled"      =>      1,
];

foreach($todiscover as $device)
{
	print "Discovering device : {$device['hostname']}...";
	$cmd = "/opt/observium/discovery.php -h {$device['device_id']}";
	$output = shell_exec($cmd);

	if($output)
	{
		print "COMPLETED!\n";
		$sql = "SELECT * FROM ports WHERE device_id = {$device['device_id']}";
		$ports = dbFetchRows($sql);

		foreach($ports as $port)
		{
			print "{$device['hostname']}: Disabling monitoring and alerting on interface {$port['ifDescr']}...";
			if(dbUpdate($portdisableparams, "ports", "port_id" . " = ?", array($port['port_id'])))
			{
				print "COMPLETED!\n";
			} else {
				print "FAILED!\n";
			}
		}
	} else {
		print "FAILED!\n";
	}
}



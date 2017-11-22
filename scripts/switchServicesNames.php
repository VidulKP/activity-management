<?php
//3.0.8 MySQL may be off

require 'config.inc.php';
require 'wampserver.lib.php';

$newServicesNames = array();
if(!empty($_SERVER['argv'][1])) {
	$numApache = intval(trim($_SERVER['argv'][1]));
	if($numApache < 0 || $numApache > 9999)
		$numApache = 0;
	$newApache = "wampapache".$numApache;
}
else
	$newApache = "wampapache";

if(!empty($_SERVER['argv'][2])) {
	$numMysql = intval(trim($_SERVER['argv'][2]));
	if($numMysql < 0 || $numMysql > 9999)
		$numMysql = 0;
	$newMysql = "wampmysqld".$numMysql;
}
else
	$newMysql = "wampmysqld";

$newServicesNames['ServiceApache'] = $newApache;
$newServicesNames['apacheServiceInstallParams'] = "-n ".$newApache." -k install";
$newServicesNames['apacheServiceRemoveParams'] = "-n ".$newApache." -k uninstall";

if($wampConf['SupportMySQL'] == 'on') {
	$newServicesNames['ServiceMysql'] = $newMysql;
	$newServicesNames['mysqlServiceInstallParams'] = "--install-manual ".$newMysql;
	$newServicesNames['mysqlServiceRemoveParams'] = "--remove ".$newMysql;
}

//Replace services names in wampmanager.conf
wampIniSet($configurationFile, $newServicesNames);

//Install new services
//Install Apache service
$command = 'start /b /wait '.$c_apacheExe.' '.$newServicesNames['apacheServiceInstallParams'];
`$command`;

//Apache service to manual start
$command = "start /b /wait SC \\\\. config ".$newApache." start= demand";
`$command`;

if($wampConf['SupportMySQL'] == 'on') {
	//Install Mysql service
	$command = 'start /b /wait '.$c_mysqlExe.' '.$newServicesNames['mysqlServiceInstallParams'];
	`$command`;
}

?>

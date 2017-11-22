<?php
// 3.0.7 Add a Listen port to Apache
require 'config.inc.php';
require 'wampserver.lib.php';
$c_listenPort = listen_ports();

//action ($_SERVER['argv'][1])
$action = trim($_SERVER['argv'][1]);
//port ($_SERVER['argv'][2])
$portToTreat = intval(trim($_SERVER['argv'][2]));

$goodPort = true;

if($action == 'add') {
	//Check validity
	if($portToTreat <= 80 || $portToTreat == 8080 || ($portToTreat > 81 && $portToTreat < 1025) || $portToTreat > 65535 || in_array($portToTreat,$c_listenPort))
		$goodPort = false;

	if($goodPort) {
		$httpdFileContents = file_get_contents($c_apacheConfFile);
		$count = 0;
		$search = array(
			"~^([ \t]*Define[ \t]+APACHE_DIR[ \t]+.*)\s?$~m",
			"~^([ \t]*Listen[ \t]+\[::0\]:80)\s?$~m",
		);
		$replace = array (
			'${1}'."\r\n".'Define MYPORT'.$portToTreat.' '.$portToTreat,
			'${1}'."\r\n".'Listen 0.0.0.0:${MYPORT'.$portToTreat.'}'."\r\n".'Listen [::0]:${MYPORT'.$portToTreat.'}',
		);
		$httpdFileContents = preg_replace($search,$replace,$httpdFileContents, -1, $count);
		if($count == 2) {
			$file_write = fopen($c_apacheConfFile,"wb");
			fwrite($file_write,$httpdFileContents);
			fclose($file_write);
		}
	}
}
elseif($action == 'delete') {
	$goodPort = true;
	//httpd.conf file
	$httpdFileContents = file_get_contents($c_apacheConfFile);
	$count = 0;
	$search = array(
		"~^Define[ \t]+MYPORT".$portToTreat."[ \t]+.*\s?$~m",
		"~^Listen[ \t]+.*MYPORT".$portToTreat.".*\s?$~m",
	);
	$replace = array (
		'',
		'',
	);
	$httpdFileContents = preg_replace($search,$replace,$httpdFileContents, -1, $count);
	if($count == 3) {
		$httpdFileContents = clean_file_contents($httpdFileContents,array(3,2));
		$file_write = fopen($c_apacheConfFile,"wb");
		fwrite($file_write,$httpdFileContents);
		fclose($file_write);
	}
	//httpd-vhosts.conf file
	$httpdVhostFileContents = file_get_contents($c_apacheVhostConfFile);
	$count = 0;
	$search = '~\$\{MYPORT'.$portToTreat.'}~mi';
	$replace = $c_UsedPort;
	$httpdVhostFileContents = preg_replace($search,$replace,$httpdVhostFileContents, -1, $count);
	if($count > 0) {
		$file_write = fopen($c_apacheVhostConfFile,"wb");
		fwrite($file_write,$httpdVhostFileContents);
		fclose($file_write);
	}
}

if(!$goodPort) {
	echo "The port number you give: ".$portToTreat."\n\n";
	echo "is not valid or already used or is default port\n";
	echo "\nPress ENTER to continue...";
  trim(fgets(STDIN));
}

?>

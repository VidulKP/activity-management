<?php
//Change 3.1.0
// Correct bug : check symlinks with strtolower
//
//
//
//
//

function wampIniSet($iniFile, $params)
{
	$iniFileContents = @file_get_contents($iniFile);
	foreach ($params as $param => $value)
	$iniFileContents = preg_replace('|^'.$param.'[ \t]*=.*|m',$param.' = '.'"'.$value.'"',$iniFileContents);
	$fp = fopen($iniFile,'w');
	fwrite($fp,$iniFileContents);
	fclose($fp);
}

function listDir($dir,$toCheck = '',$racine='') {
	$list = array();
	if(is_dir($dir)) {
		if ($handle = opendir($dir)) {
			while (false !== ($file = readdir($handle))) {
				if ($file != "." && $file != ".." && is_dir($dir.'/'.$file)) {
					if (!empty($toCheck)) {
						if(call_user_func($toCheck,$dir,$file,$racine))
							$list[] = $file;
					}
				}
			}
			closedir($handle);
		}
	}
	return $list;
}

function checkPhpConf($baseDir,$version,$racine) {
	global $wampBinConfFiles, $phpConfFileForApache;
  if(strpos($version,$racine) === 0)
		return (file_exists($baseDir.'/'.$version.'/'.$wampBinConfFiles) && file_exists($baseDir.'/'.$version.'/'.$phpConfFileForApache));
	else
		return false;
}

function checkApacheConf($baseDir,$version,$racine) {
	global $wampBinConfFiles;
  if(strpos($version,$racine) === 0)
		return file_exists($baseDir.'/'.$version.'/'.$wampBinConfFiles);
	else
		return false;
}

function checkMysqlConf($baseDir,$version,$racine) {
	global $wampBinConfFiles;
  if(strpos($version,$racine) === 0)
		return file_exists($baseDir.'/'.$version.'/'.$wampBinConfFiles);
	else
		return false;
}

function checkMariaDBConf($baseDir,$version,$racine) {
  global $wampBinConfFiles;
  if(strpos($version,$racine) === 0)
		return file_exists($baseDir.'/'.$version.'/'.$wampBinConfFiles);
	else
		return false;
}

function linkPhpDllToApacheBin($php_version) {
	global $phpDllToCopy, $c_phpVersionDir, $c_apacheVersionDir, $wampConf, $phpConfFileForApache;

	//Create symbolic link instead of copy dll's files
	clearstatcache();
	foreach ($phpDllToCopy as $dll)
	{
		$target = $c_phpVersionDir.'/php'.$php_version.'/'.$dll;
		$link = $c_apacheVersionDir.'/apache'.$wampConf['apacheVersion'].'/'.$wampConf['apacheExeDir'].'/'.$dll;
		//File or symlink deleted if exists
		if(is_file($link) || is_link($link)) {
			unlink($link);
		}
		//Symlink created if file exists in phpx.y.z directory
		if (is_file($target)) {
			if(symlink($target, $link) === false)
				error_log("Error while creating symlink '".$link."' to '".$target."'");
		}
	}
	//Create apache/apachex.y.z/bin/php.ini link to phpForApache.ini file of active version of PHP
	$target = $c_phpVersionDir."/php".$php_version."/".$phpConfFileForApache;
	$link = $c_apacheVersionDir."/apache".$wampConf['apacheVersion']."/".$wampConf['apacheExeDir']."/php.ini";
	//php.ini deleted if exists
	if(is_file($link) || is_link($link)) {
		unlink($link);
	}
	if(symlink($target, $link) === false)
		error_log("Error while creating symlink '".$link."' to '".$target."'");
}

function CheckSymlink($php_version) {
	global $phpDllToCopy, $c_phpVersionDir, $c_apacheVersionDir, $wampConf, $phpConfFileForApache;

	$error = '';

	//Check if necessary symlinks exists
	clearstatcache();
	foreach ($phpDllToCopy as $dll)
	{
		$target = $c_phpVersionDir.'/php'.$php_version.'/'.$dll;
		$link = $c_apacheVersionDir.'/apache'.$wampConf['apacheVersion'].'/'.$wampConf['apacheExeDir'].'/'.$dll;
		//Check Symlink if file exists in phpx.y.z directory
		if(is_file($target)) {
			if(is_link($link)) {
				$real_link = str_replace("\\", "/",readlink($link));
				if(strtolower($real_link) != strtolower($target)) {
					$error .= "Symbolic link ".$link."\n      is: ".$real_link."\nshould be ".$target."\n\n";
				}
			}
			elseif(is_file($link)) {
				$error .= "File ".$link." exists.\nShould be a symbolic link\n";
			}
			else {
				$error .= "Symbolic link ".$link." does not exist\n";
			}
		}
	}
	//Verify apache/apachex.y.z/bin/php.ini link to phpForApache.ini file of active version of PHP
	$target = $c_phpVersionDir."/php".$php_version."/".$phpConfFileForApache;
	$link = $c_apacheVersionDir."/apache".$wampConf['apacheVersion']."/".$wampConf['apacheExeDir']."/php.ini";
	if(is_link($link)) {
		$real_link = str_replace("\\", "/",readlink($link));
		if(strtolower($real_link) != strtolower($target)) {
			$error .= "Symbolic link: ".$link."\nTarget is       : ".$real_link."\nTarget should be: ".$target."\n";
		}
	}
	elseif(is_file($link)) {
		$error .= "File ".$link." exists.\nShould be a symbolic link\n";
	}
	else {
		$error .= "Symbolic link ".$link." does not exist\n";
	}
	return $error;
}

function switchPhpVersion($newPhpVersion)
{
	require 'config.inc.php';

	//loading the configuration file of the new version
	require $c_phpVersionDir.'/php'.$newPhpVersion.'/'.$wampBinConfFiles;

	//the httpd.conf texts depending on the version of apache is determined
	$apacheVersion = $wampConf['apacheVersion'];
	while (!isset($phpConf['apache'][$apacheVersion]) && $apacheVersion != '')
	{
		$pos = strrpos($apacheVersion,'.');
		$apacheVersion = substr($apacheVersion,0,$pos);

	}

	// modifying the conf apache file
	$httpdContents = file($c_apacheConfFile);
	$newHttpdContents = '';
	foreach ($httpdContents as $line)
	{
		if (strstr($line,'LoadModule') && strstr($line,'php'))
		{
			$newHttpdContents .= 'LoadModule '.$phpConf['apache'][$apacheVersion]['LoadModuleName'].' "'.$c_phpVersionDir.'/php'.$newPhpVersion.'/'.$phpConf['apache'][$apacheVersion]['LoadModuleFile'].'"'."\r\n";
		}
    elseif (!empty($phpConf['apache'][$apacheVersion]['AddModule']) && strstr($line,'AddModule') && strstr($line,'php'))
    	$newHttpdContents .= 'AddModule '.$phpConf['apache'][$apacheVersion]['AddModule']."\r\n";
		else
			$newHttpdContents .= $line;
	}
	file_put_contents($c_apacheConfFile,$newHttpdContents);


	//modifying the conf of WampServer
	$wampIniNewContents['phpIniDir'] = $phpConf['phpIniDir'];
	$wampIniNewContents['phpExeDir'] = $phpConf['phpExeDir'];
	$wampIniNewContents['phpConfFile'] = $phpConf['phpConfFile'];
	$wampIniNewContents['phpVersion'] = $newPhpVersion;
	wampIniSet($configurationFile, $wampIniNewContents);

	//Create symbolic link to php dll's and to phpForApache.ini of new version
	linkPhpDllToApacheBin($newPhpVersion);

}

// Create parameter in $configurationFile file
// $name = parameter name -- $value = parameter value
// $section = name of the section to add parameter after
function createWampConfParam($name, $value, $section, $configurationFile) {
	$wampConfFileContents = @file_get_contents($configurationFile) or die ($configurationFile."file not found");
	$addTxt = $name.' = "'.$value.'"';
	$wampConfFileContents = str_replace($section,$section."\r\n".$addTxt,$wampConfFileContents);
	$fpWampConf = fopen($configurationFile,"w");
	fwrite($fpWampConf,$wampConfFileContents);
	fclose($fpWampConf);
}

//**** Functions to check if IP is valid and/or in a range ****
/*
 * ip_in_range.php - Function to determine if an IP is located in a
 * specific range as specified via several alternative formats.
 *
 * Network ranges can be specified as:
 * 1. Wildcard format:     1.2.3.*
 * 2. CIDR format:         1.2.3/24  OR  1.2.3.4/255.255.255.0
 * 3. Start-End IP format: 1.2.3.0-1.2.3.255
 *
 * Return value BOOLEAN : ip_in_range($ip, $range);
 *
 * Copyright 2008: Paul Gregg <pgregg@pgregg.com>
 * 10 January 2008
 * Version: 1.2
 *
 * Source website: http://www.pgregg.com/projects/php/ip_in_range/
 * Version 1.2
 * Please do not remove this header, or source attibution from this file.
 */

// decbin32
// In order to simplify working with IP addresses (in binary) and their
// netmasks, it is easier to ensure that the binary strings are padded
// with zeros out to 32 characters - IP addresses are 32 bit numbers
function decbin32 ($dec) {
  return str_pad(decbin($dec), 32, '0', STR_PAD_LEFT);
}

// ip_in_range
// This function takes 2 arguments, an IP address and a "range" in several
// different formats.
// Network ranges can be specified as:
// 1. Wildcard format:     1.2.3.*
// 2. CIDR format:         1.2.3/24  OR  1.2.3.4/255.255.255.0
// 3. Start-End IP format: 1.2.3.0-1.2.3.255
// The function will return true if the supplied IP is within the range.
// Note little validation is done on the range inputs - it expects you to
// use one of the above 3 formats.
function ip_in_range($ip, $range) {
  if (strpos($range, '/') !== false) {
    // $range is in IP/NETMASK format
    list($range, $netmask) = explode('/', $range, 2);
    if (strpos($netmask, '.') !== false) {
      // $netmask is a 255.255.0.0 format
      $netmask = str_replace('*', '0', $netmask);
      $netmask_dec = ip2long($netmask);
      return ( (ip2long($ip) & $netmask_dec) == (ip2long($range) & $netmask_dec) );
    } else {
      // $netmask is a CIDR size block
      // fix the range argument
      $x = explode('.', $range);
      while(count($x)<4) $x[] = '0';
      list($a,$b,$c,$d) = $x;
      $range = sprintf("%u.%u.%u.%u", empty($a)?'0':$a, empty($b)?'0':$b,empty($c)?'0':$c,empty($d)?'0':$d);
      $range_dec = ip2long($range);
      $ip_dec = ip2long($ip);

      # Strategy 1 - Create the netmask with 'netmask' 1s and then fill it to 32 with 0s
      #$netmask_dec = bindec(str_pad('', $netmask, '1') . str_pad('', 32-$netmask, '0'));

      # Strategy 2 - Use math to create it
      $wildcard_dec = pow(2, (32-$netmask)) - 1;
      $netmask_dec = ~ $wildcard_dec;

      return (($ip_dec & $netmask_dec) == ($range_dec & $netmask_dec));
    }
  } else {
    // range might be 255.255.*.* or 1.2.3.0-1.2.3.255
    if (strpos($range, '*') !==false) { // a.b.*.* format
      // Just convert to A-B format by setting * to 0 for A and 255 for B
      $lower = str_replace('*', '0', $range);
      $upper = str_replace('*', '255', $range);
      $range = "$lower-$upper";
    }

    if (strpos($range, '-')!==false) { // A-B format
      list($lower, $upper) = explode('-', $range, 2);
      $lower_dec = (float)sprintf("%u",ip2long($lower));
      $upper_dec = (float)sprintf("%u",ip2long($upper));
      $ip_dec = (float)sprintf("%u",ip2long($ip));
      return ( ($ip_dec>=$lower_dec) && ($ip_dec<=$upper_dec) );
    }

    error_log('Range argument is not in 1.2.3.4/24 or 1.2.3.4/255.255.255.0 format');
    return false;
  }
}
function check_IP($ip, $local_ip = true, $all_local = false) {
	global $wampConf;
	$valid = false;
	if(preg_match('/^(?:(?:25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])\.){3}(?:25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]?|[0-9])$/', $ip) == 0)
		return false;
	if($local_ip) {
		$range = '127.0.0.1-127.255.255.255';
		if(ip_in_range($ip,$range))
			$valid = true;
		if($wampConf['VhostAllLocalIp'] == 'on')
			$all_local = true;
		if($all_local && !$valid) {
			$ranges = array('10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16');
			foreach($ranges as $value) {
				if(ip_in_range($ip, $value)) {
					$valid = true;
					break;
				}
			}
		}
	}
	return $valid;
}

//Function to replace Apache variable name by it contents
function replace_apache_var($chemin) {
	global $c_ApacheDefine;
	if(preg_match('~\${(.+)}~',$chemin,$var) > 0) {
		if(array_key_exists($var[1],$c_ApacheDefine)) {
			$chemin = str_replace($var[0],trim($c_ApacheDefine[$var[1]]),$chemin);
		}
		else {
			error_log('Apache variable "'.$var[0].'" is not defined.');
		}
	}
	return $chemin;
}
// Function to retrieve Apache Listen ports
function listen_ports() {
	global $c_apacheConfFile;
	$httpdFileContents = file_get_contents($c_apacheConfFile);
	preg_match_all("~^Listen[ \t]+.*:(\S*)\s*$~m",$httpdFileContents, $matches);
	$c_listenPort = array_values(array_map('replace_apache_var',array_unique($matches[1])));
	sort($c_listenPort);
	return $c_listenPort;
}

// Function to check if VirtualHost exist and are valid
function check_virtualhost($check_files_only = false) {
	global $wampConf, $c_apacheConfFile, $c_apacheVhostConfFile;
	clearstatcache();
	$virtualHost = array(
		'include_vhosts' => true,
		'vhosts_exist' => true,
		'nb_Server' => 0,
		'ServerName' => array(),
		'ServerNameIp' => array(),
		'ServerNamePort' => array(),
		'ServerNameValid' => array(),
		'ServerNameIpValid' => array(),
		'ServerNamePortValid' => array(),
		'ServerNamePortListen' => array(),
		'FirstServerName' => '',
		'nb_Virtual' => 0,
		'nb_Virtual_Port' => 0,
		'virtual_port' => array(),
		'virtual_ip' => array(),
		'nb_Document' => 0,
		'documentPath' => array(),
		'documentPathValid' => array(),
		'document' => true,
		'nb_Directory' => 0,
		'nb_End_Directory' => 0,
		'directoryPath' => array(),
		'directoryPathValid' => array(),
		'directory' => true,
		'port_number' => true,
		'nb_duplicate' => 0,
		'duplicate' => array(),
		'nb_duplicateIp' => 0,
		'duplicateIp' => array(),
		'nb_NotListenPort' => 0,
		'port_listen' => true,
		'NotListenPort' => array(),
	);
	$httpConfFileContents = file_get_contents($c_apacheConfFile);
	//is Include conf/extra/httpd-vhosts.conf uncommented?
	if(preg_match("~^[ \t]*#[ \t]*Include[ \t]+conf/extra/httpd-vhosts.conf.*$~m",$httpConfFileContents) > 0) {
		$virtualHost['include_vhosts'] = false;
		return $virtualHost;
	}

	$virtualHost['vhosts_file'] = $c_apacheVhostConfFile;
	if(!file_exists($virtualHost['vhosts_file'])) {
		$virtualHost['vhosts_exist'] = false;
		return $virtualHost;
	}
	if($check_files_only) {
		return $virtualHost;
	}

	$myVhostsContents = file_get_contents($virtualHost['vhosts_file']);
	// Extract values of ServerName (without # at the beginning of the line)
	$nb_Server = preg_match_all("/^(?![ \t]*#).*ServerName[ \t]+(.*?\r?)$/m", $myVhostsContents, $Server_matches);
	// Extract values of <VirtualHost *:xx> or <VirtualHost ip:xx> port number
	$nb_Virtual = preg_match_all("/^(?![ \t]*#).*<VirtualHost[ \t]+(?:\*|([0-9.]*)):(.*)>\R/m", $myVhostsContents, $Virtual_matches);
	//error_log("Virtual_matches=".print_r($Virtual_matches,true));
	// Extract values of DocumentRoot path
	$nb_Document = preg_match_all("/^(?![ \t]*#).*DocumentRoot[ \t]+(.*?\r?)$/m", $myVhostsContents, $Document_matches);
	// Count number of <Directory that has to match the number of ServerName
	$nb_Directory = preg_match_all("/^(?![ \t]*#).*<Directory[ \t]+(.*)>\R/m", $myVhostsContents, $Dir_matches);
	$nb_End_Directory = preg_match_all("~^(?![ \t]*#).*</Directory.*$~m", $myVhostsContents, $end_Dir_matches);
	$server_name = array();
	if($nb_Server == 0) {
		$virtualHost['nb_server'] = 0;
		return $virtualHost;
	}
	$virtualHost['nb_Server'] = $nb_Server;
	$virtualHost['nb_Virtual'] = $nb_Virtual;
	$virtualHost['nb_Virtual_Port'] = count($Virtual_matches[2]);
	$virtualHost['nb_Document'] = $nb_Document;
	$virtualHost['nb_Directory'] = $nb_Directory;
	$virtualHost['nb_End_Directory'] = $nb_End_Directory;
	//Check validity of port number
	$port_ref = $Virtual_matches[2][0];
	$virtualHost['virtual_port'] = array_merge($Virtual_matches[0]);
	$virtualHost['virtual_ip'] = array_merge($Virtual_matches[1]);
	for($i = 0 ; $i < count($Virtual_matches[1]) ; $i++) {
		$value = trim($Server_matches[1][$i]);
		$virtualHost['virtual_port'][$i] = replace_apache_var($virtualHost['virtual_port'][$i]);
		$port = $Virtual_matches[2][$i];
		$port = replace_apache_var($port);
		$virtualHost['ServerNamePort'][$value] = $port;
		$virtualHost['ServerNamePortValid'][$value]	= true;
		$virtualHost['ServerNamePortListen'][$value]	= true;
		if(empty($port) || !is_numeric($port) || $port < 80 || $port > 65535) {
			$virtualHost['ServerNamePortValid'][$value]	= false;
			$virtualHost['port_number'] = false;
		}
	}

	//Check validity of DocumentRoot
	for($i = 0 ; $i < $nb_Document ; $i++) {
		$chemin = trim($Document_matches[1][$i], " \t\n\r\0\x0B\"");
		$chemin = replace_apache_var($chemin);
		$virtualHost['documentPath'][$i] = $chemin;
		if((!file_exists($chemin) || !is_dir($chemin)) && $wampConf['NotCheckVirtualHost'] == 'off') {
			$virtualHost['documentPathValid'][$chemin] = false;
			$virtualHost['document'] = false;
		}
		else
			$virtualHost['documentPathValid'][$chemin] = true;
	}

	//Check validity of Directory path
	for($i = 0 ; $i < $nb_Directory ; $i++) {
		$chemin = trim($Dir_matches[1][$i], " \t\n\r\0\x0B\"");
		$chemin = replace_apache_var($chemin);
		$virtualHost['directoryPath'][$i] = $chemin;
		if((!file_exists($chemin) || !is_dir($chemin)) && $wampConf['NotCheckVirtualHost'] == 'off') {
			$virtualHost['directoryPathValid'][$chemin] = false;
			$virtualHost['directory'] = false;
		}
		else
			$virtualHost['directoryPathValid'][$chemin] = true;
	}

	//Check validity of ServerName
	$TempServerName = array();
	$TempServerIp = array();
	for($i = 0 ; $i < $nb_Server ; $i++) {
		$value = trim($Server_matches[1][$i]);
		$TempServerName[] = $value;
		if($i == 0)
			$virtualHost['FirstServerName'] = $value;
		$virtualHost['ServerName'][$value] = $value;
		$virtualHost['ServerNameIp'][$value] = false;
		$virtualHost['ServerNameIpValid'][$value] = false;

		//Validity of ServerName (Like domain name)
		//   /^[A-Za-z]+([A-Za-z0-9][-.](?![-.])|[A-Za-z0-9]){1,60}[A-Za-z0-9]$/
		if(preg_match('/^
		  [A-Za-z]+ 			# letter in first place
			([A-Za-z0-9]		# letter or number
			[-.](?![-.])		#  a . or - not followed by . or -
						|					#   or
			[A-Za-z0-9]			#  a letter or a number
			){1,60}					# this, repeated from 1 to 60 times
			[A-Za-z0-9]			# letter or number at the end
			$/x',$value) == 0 && $wampConf['NotCheckVirtualHost'] == 'off') {
			$virtualHost['ServerNameValid'][$value] = false;
		}
		elseif(strpos($value,"dummy-host") !== false || strpos($value,"example.com") !== false) {
			$virtualHost['ServerNameValid'][$value] = 'dummy';
		}
		else {
			$virtualHost['ServerNameValid'][$value] = true;
			//Check optionnal IP
			if(!empty($virtualHost['virtual_ip'][$i])) {
				$Virtual_IP = $virtualHost['virtual_ip'][$i];
				$virtualHost['ServerNameIp'][$value] = $Virtual_IP;
				if(check_IP($Virtual_IP)) {
					$virtualHost['ServerNameIpValid'][$value] = true;
					$TempServerIp[] = $Virtual_IP;
				}
			}
		}
	} //End for

	//Check if duplicate ServerName
	if($wampConf['NotCheckDuplicate'] == 'off' && $wampConf['NotCheckVirtualHost'] == 'off') {
		$array_unique = array_unique($TempServerName);
		if (count($TempServerName) - count($array_unique) != 0 ){
			$virtualHost['nb_duplicate'] = count($TempServerName) - count($array_unique);
    	for ($i=0; $i < count($TempServerName); $i++) {
    		if (!array_key_exists($i, $array_unique))
      		$virtualHost['duplicate'][] = $TempServerName[$i];
    	}
		}
		//Check duplicate Ip
		$array_unique = array_unique($TempServerIp);
		if (count($TempServerIp) - count($array_unique) != 0 ){
			$virtualHost['nb_duplicateIp'] = count($TempServerIp) - count($array_unique);
    	for ($i=0; $i < count($TempServerIp); $i++) {
    		if (!array_key_exists($i, $array_unique))
      		$virtualHost['duplicateIp'][] = $TempServerIp[$i];
    	}
		}
	}

	//Check VirtualHost port not Listen port in httpd.conf
	$diffVL = array_diff(array_values(array_unique(array_values($virtualHost['ServerNamePort']))),listen_ports());
	if(count($diffVL) > 0) {
		$virtualHost['port_listen'] = false;
		$virtualHost['nb_NotListenPort'] = count($diffVL);
	foreach($diffVL as $value)
		$virtualHost['NotListenPort'] += array_fill_keys(array_keys($virtualHost['ServerNamePort'],$value),$value);
	foreach($virtualHost['NotListenPort'] as $key => $value)
		$virtualHost['ServerNamePortListen'][$key] = $value;
	}

	if($wampConf['NotCheckVirtualHost'] == 'on') {
		$virtualHost['nb_Server'] = $virtualHost['nb_Virtual'];
		$virtualHost['nb_Document'] = $virtualHost['nb_Virtual'];
		$virtualHost['nb_Directory'] = $virtualHost['nb_Virtual'];
		$virtualHost['nb_End_Directory'] = $virtualHost['nb_Virtual'];
		$virtualHost['nb_duplicateIp'] = 0;
		$virtualHost['nb_duplicate'] = 0;
		$virtualHost['port_number'] = true;
		$virtualHost['port_listen'] = true;
		$virtualHost['nb_NotListenPort'] = 0;
	}
	//error_log(print_r($virtualHost, true));
	return $virtualHost;
}

// List all versions PHP, MySQL, Apache into array
// Indicate one's in use or CLI
function ListVersions($used = false, $toutes = false) {
	global $c_phpVersionDir, $c_phpVersion,$c_phpCliVersion,
		$c_apacheVersionDir,$c_apacheVersion,
		$c_mysqlVersionDir,$c_mysqlVersion,
		$c_mariadbVersionDir,$c_mariadbVersion,
		$wampConf;
	$Versions = array(
		'php' => array(),
		'mysql' => array(),
		'apache' => array(),
		'mariadb' => array(),
	);
	//PHP versions
	$phpVersionList = listDir($c_phpVersionDir,'checkPhpConf','php');
	foreach ($phpVersionList as $onePhp) {
		$onePhpVersion = str_ireplace('php','',$onePhp);
		$maydelete = true;
		if(!$toutes && ($onePhpVersion == $c_phpVersion)) {
			$onePhpVersion .= 'USED';
			$maydelete = false;
		}
		if(!$toutes && ($onePhpVersion == $c_phpCliVersion)) {
			$onePhpVersion .= 'CLI';
			$maydelete = false;
		}
		if($maydelete || $used)
			$Versions['php'][] = $onePhpVersion;
	}
	//MySQL versions
	$mysqlVersionList = listDir($c_mysqlVersionDir,'checkMysqlConf','mysql');
	foreach ($mysqlVersionList as $oneMysql) {
  	$oneMysqlVersion = str_ireplace('mysql','',$oneMysql);
		$maydelete = true;
  	if(!$toutes && $wampConf['SupportMySQL'] == 'on' && ($oneMysqlVersion == $c_mysqlVersion)) {
  		$oneMysqlVersion .= 'USED';
			$maydelete = false;
  	}
		if($maydelete || $used)
	  	$Versions['mysql'][] = $oneMysqlVersion;
	}
	//Apache versions
	$apacheVersionList = listDir($c_apacheVersionDir,'checkApacheConf','apache');
	foreach ($apacheVersionList as $oneApache) {
  	$oneApacheVersion = str_ireplace('apache','',$oneApache);
		$maydelete = true;
  	if(!$toutes && ($oneApacheVersion == $c_apacheVersion)) {
  		$oneApacheVersion .= 'USED';
			$maydelete = false;
  	}
		if($maydelete || $used)
	  	$Versions['apache'][] = $oneApacheVersion;
	}
	//MariaDB versions
	$mariadbVersionList = listDir($c_mariadbVersionDir,'checkMariaDBConf','mariadb');
	foreach ($mariadbVersionList as $oneMariadb) {
  	$oneMariadbVersion = str_ireplace('mariadb','',$oneMariadb);
		$maydelete = true;
  	if(!$toutes && $wampConf['SupportMariaDB'] == 'on' && ($oneMariadbVersion == $c_mariadbVersion)) {
  		$oneMariadbVersion .= 'USED';
			$maydelete = false;
  	}
		if($maydelete || $used)
	  	$Versions['mariadb'][] = $oneMariadbVersion;
	}

	return $Versions;
}
// Get content of file and set lines end to DOS (CR/LF) if needed
function file_get_contents_dos($file, $retour = true) {
	$check_DOS = @file_get_contents($file) or die ($file."file not found");
	//Check if there is \n without previous \r
	if(preg_match("/(?<!\r)\n/",$check_DOS) > 0) {
		$check_DOS = preg_replace(array('/\r\n?/','/\n/'),array("\n","\r\n"), $check_DOS);
		$file_write = fopen($file,"w");
		fwrite($file_write,$check_DOS);
		fclose($file_write);
	}
	if($retour) return $check_DOS;
}

// Clean file contents
function clean_file_contents($contents, $twoToNone = array(2,0), $all_spaces = false) {
	if($all_spaces) {
		//more than one space into one space
		$contents = preg_replace("~[ \t]{2,}~",' ',$contents);
	}
	//suppress spaces or tabs at the end of lines
	$contents = preg_replace('~[ \t]+(\r?)$~m',"$1",$contents);
	//suppress more than $twoToNone[0] empty line into $twoToNone[1] empty lines
	// For Unix, Windows, Mac OS X & old Mac OS Classic
	/* "/^(?:[\t ]*(?>\r?\n|\r)){2,}/m" */
	// For Unix, Windows & Mac OS X (Without old Mac OS Classic)
	// "/^(?:[\t\r ]*\n){2,}/m"
	$contents = preg_replace("/^(?:[\t\r ]*\n){".$twoToNone[0].",}/m",str_repeat("\r\n",$twoToNone[1]),$contents);
	return $contents;
}

//Check alias and paths in httpd-autoindex.conf
// Alias /icons/ "c:/Apache24/icons/" => Alias /icons/ "icons/"
// <Directory "c:/Apache24/icons"> => <Directory "icons">
function check_autoindex() {
	global $c_apacheAutoIndexConfFile;
	$autoindexContents = @file_get_contents($c_apacheAutoIndexConfFile) or die ("httpd-autoindex.conf file not found");
	$autoindexContents = preg_replace("~^(Alias /icons/) (\".+icons/\")\r?$~m","$1 ".'"icons/"',$autoindexContents,1,$count1);
	$autoindexContents = preg_replace("~^(<Directory) (\".+icons\")>\r?$~m","$1 ".'"icons">',$autoindexContents,1,$count2);

	if($count1 == 1 || $count2 == 1) {
		$file_write = fopen($c_apacheAutoIndexConfFile,"w");
		fwrite($file_write,$autoindexContents);
		fclose($file_write);
	}
}

// Function test of IPv6 support
function test_IPv6() {
	if (extension_loaded('sockets')) {
		//Create socket IPv6
		$socket = socket_create(AF_INET6, SOCK_STREAM, SOL_TCP);
		if($socket === false) {
			$errorcode = socket_last_error() ;
			$errormsg = socket_strerror($errorcode);
			//echo "<p>Error socket IPv6: ".$errormsg."</p>\n" ;
			error_log("For information only: IPv6 not supported");
			return false;
		}
		else {
			//echo "<p>IPv6 supported</p>\n" ;
			socket_close($socket);
			error_log("For information only: IPv6 supported");
			return true;
		}
	}
	else {
		error_log("Extension PHP 'sockets' not loaded, cannot check support of IPv6");
		return false;
	}
}

?>

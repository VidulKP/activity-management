<?php
// Update 3.0.7
// Use preg_match for spaces or tabs

require 'config.inc.php';

$phpIniFileContents = @file_get_contents($c_phpConfFile) or die ("php.ini file not found");

if ($_SERVER['argv'][2] == 'off') {
	if(preg_match('/^'.$_SERVER['argv'][1].'\s*=\s*On/im',$phpIniFileContents,$matchesON) !== false)
		$findTxt = $matchesON[0];
	else
		$findTxt  = $_SERVER['argv'][1].' = On';
	$replaceTxt  = $_SERVER['argv'][1].' = Off';
}
else {
	if(preg_match('/^'.$_SERVER['argv'][1].'\s*=\s*Off/im',$phpIniFileContents,$matchesOFF) !== false)
		$findTxt = $matchesOFF[0];
	else
		$findTxt  = $_SERVER['argv'][1].' = Off';
	$replaceTxt  = $_SERVER['argv'][1].' = On';
}

$phpIniFileContents = str_ireplace($findTxt,$replaceTxt,$phpIniFileContents);

$fpPhpIni = fopen($c_phpConfFile,"w");
fwrite($fpPhpIni,$phpIniFileContents);
fclose($fpPhpIni);


?>
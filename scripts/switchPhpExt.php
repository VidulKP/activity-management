<?php
// Update 3.0.9
// Support for zend_extension

require 'config.inc.php';

$phpIniFileContents = @file_get_contents($c_phpConfFile) or die ("php.ini file not found");
$zend = $quote = '';
$mode = $_SERVER['argv'][2];
if(strpos($mode,'zend') !== false) {
	$zend = 'zend_';
	$quote = '"';
	$mode = substr($mode,4);
}

// on remplace la ligne
if ($mode == 'on') {
	if(preg_match('~^;'.$zend.'extension\s*=\s*"?'.$_SERVER['argv'][1].'\.dll"?~im',$phpIniFileContents,$matchesOFF) !== false)
		$findTxt = $matchesOFF[0];
	else
		$findTxt  = ';'.$zend.'extension='.$_SERVER['argv'][1].'.dll';
	$replaceTxt  = $zend.'extension='.$quote.$_SERVER['argv'][1].'.dll'.$quote;
}
elseif ($mode == 'off') {
	if(preg_match('~^'.$zend.'extension\s*=\s*"?'.$_SERVER['argv'][1].'\.dll"?~im',$phpIniFileContents,$matchesON) !== false)
		$findTxt = $matchesON[0];
	else
		$findTxt  = $zend.'extension='.$_SERVER['argv'][1].'.dll';
	$replaceTxt  = ';'.$zend.'extension='.$quote.$_SERVER['argv'][1].'.dll'.$quote;
}
else
	exit;
$phpIniFileContents2 = str_replace($findTxt,$replaceTxt,$phpIniFileContents);


// on ajoute la ligne si elle n'existe pas
if ($phpIniFileContents2 == $phpIniFileContents) {
	$findTxt  = <<< EOF
;;;;;;;;;;;;;;;;;;;
; Module Settings ;
EOF;

	$replaceTxt  = <<< EOF
	{$zend}extension={$quote}{$_SERVER['argv'][1]}.dll{$quote}
;;;;;;;;;;;;;;;;;;;
; Module Settings ;
EOF;

	$phpIniFileContents2 = str_replace($findTxt,$replaceTxt,$phpIniFileContents);
}

$fpPhpIni = fopen($c_phpConfFile,"w");
fwrite($fpPhpIni,$phpIniFileContents2);
fclose($fpPhpIni);

?>
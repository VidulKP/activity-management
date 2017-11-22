<?php
//3.0.6 Change MySQL parameters not on or off

require 'config.inc.php';

$myIniFileContents = @file_get_contents($c_mysqlConfFile) or die ("my.ini file not found");

$quoted = false;
if($_SERVER['argv'][1] == 'quotes')
	$quoted = true;
$parameter = $_SERVER['argv'][2];
$newvalue = $_SERVER['argv'][3];
$changeError = '';

if(!empty($_SERVER['argv'][4])) {
	$choose = $_SERVER['argv'][4];
	if($choose == 'Seconds') {
		if(preg_match('/^[1-9][0-9]{1,3}$/m',$newvalue) != 1) {
		$changeError = <<< EOFERROR
The value you entered ({$newvalue}) is out of range.
The number of seconds must be between 10 and 9999.
The value is set to 60 seconds by default.
EOFERROR;
		$newvalue = '60';
		}
	}
	elseif($choose == 'Size') {
		$newvalue = strtoupper($newvalue);
		if(preg_match('/^[1-9][0-9]{1,3}(M|G)$/m',$newvalue) != 1) {
		$changeError = <<< EOF1ERROR
The value you entered ({$newvalue}) is out of range.
The number must be between 10 and 9999.
The number must be followed by M (For Mega) or G (For Giga)
The value is set to 128M by default.
EOF1ERROR;
		$newvalue = '128M';
		}
	}
}
if($quoted)
	$newvalue = '"'.$newvalue.'"';

//if sql-mode
$count = 0;
if($parameter == 'sql-mode') {
	if($newvalue == 'none') {
		$myIniFileContents = preg_replace('/^sql-mode.*$/m',';${0}',$myIniFileContents,-1, $count);
		if(strpos($myIniFileContents,";sql-mode=\"\"") !== false) {
			$myIniFileContents = str_replace(";sql-mode=\"\"","sql-mode=\"\"",$myIniFileContents,$count);
		}
		else {
			//add sql-mode="" under section [wampmysqld]
			$section = '['.$c_mysqlService.']';
			$addTxt = 'sql-mode=""';
			$myIniFileContents = str_replace($section,$section."\r\n".$addTxt,$myIniFileContents,$count);
		}
	}
	elseif($newvalue == 'default') {
		$myIniFileContents = preg_replace('/^sql-mode.*$/m',';${0}',$myIniFileContents,-1, $count);
	}
	elseif($newvalue == 'user') {
		$myIniFileContents = preg_replace('/^sql-mode.*$/m',';${0}',$myIniFileContents);
		$myIniFileContents = preg_replace('/^;(sql-mode[ \t]*=[ \t]*"[^"].*)$/m','${1}',$myIniFileContents,-1, $count);
	}
}
else {
	//Number of replacements limited to 1 to replace only in the first section [wampmysqld]
	$myIniFileContents = preg_replace('|^'.$parameter.'[ \t]*=.*|m',$parameter.' = '.$newvalue,$myIniFileContents, 1, $count);
}

if($count > 0) {
	$fpMyIni = fopen($c_mysqlConfFile,"w");
	fwrite($fpMyIni,$myIniFileContents);
	fclose($fpMyIni);
}
if(!empty($changeError)) {
	echo "********************* WARNING ********************\n\n";
	echo $changeError;
	echo "\nPress ENTER to continue...";
  trim(fgets(STDIN));
}

?>
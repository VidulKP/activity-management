<?php
// script to change MySQL port used
// 3.0.7 - Verify port reserved for mariaDB
// 3.0.8 - Change MySQL port only.

require 'config.inc.php';
require 'wampserver.lib.php';

//Replace UsedMysqlPort by NewMysqlport ($_SERVER['argv'][1])
$portToUse = intval(trim($_SERVER['argv'][1]));
//Check validity
$goodPort = true;
if($portToUse < 3301 || $portToUse > 3309 || $portToUse == $wampConf['mariaPortUsed'])
	$goodPort = false;

$myIniReplace = false;

if($goodPort) {
	//Change port into my.ini
	$mySqlIniFileContents = @file_get_contents($c_mysqlConfFile) or die ("my.ini file not found");
	$nb_myIni = 0; //must be three replacements: [client], [wampmysqld] and [mysqld] groups
	$findTxtRegex = array(
	'/^(port)[ \t]*=.*$/m',
	);
	$mySqlIniFileContents = preg_replace($findTxtRegex,"$1 = ".$portToUse, $mySqlIniFileContents, -1, $nb_myIni);
	if($nb_myIni == 3)
		$myIniReplace = true;

	if($myIniReplace) {
		$myIni = fopen($c_mysqlConfFile ,"w");
		fwrite($myIni,$mySqlIniFileContents);
		fclose($myIni);
		$myIniConf['mysqlPortUsed'] = $portToUse;
		if($portToUse == $c_DefaultMysqlPort)
			$myIniConf['mysqlUseOtherPort'] = "off";
		else
			$myIniConf['mysqlUseOtherPort'] = "on";
		wampIniSet($configurationFile, $myIniConf);
	}
}

else {
	echo "The port number you give: ".$portToUse."\n\n";
	echo "is not valid\n";
	echo "Must be between 3301 and 3309\nbut not ".$wampConf['mariaPortUsed']." that is reserved for MariaDB\n";
	echo "\nPress ENTER to continue...";
  trim(fgets(STDIN));
}

?>

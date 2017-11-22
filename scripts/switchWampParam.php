<?php
//3.0.8 - Check MariaDB version installed is that declared in wampmanager.conf

require 'config.inc.php';
require 'wampserver.lib.php';

if($_SERVER['argv'][2] == 'create') {
	createWampConfParam($_SERVER['argv'][1],$_SERVER['argv'][3],$_SERVER['argv'][4],$configurationFile);
}
else {
	$error = '';
	$goodParam = true;
	if($_SERVER['argv'][1] == 'SupportMariaDB') {
		if(file_exists($c_mariadbVersionDir) && is_dir($c_mariadbVersionDir)) {
			if($_SERVER['argv'][2] == 'on') {
  			$mariadbVersionList = listDir($c_mariadbVersionDir,'checkMariaDBConf','mariadb');
  			if(count($mariadbVersionList) == 0) {
  				$goodParam = false;
  				$error .= "No version of MariaDB is installed.\n";
  			}
  			else {
  				//Check if mariadb version installed is that in wampmanager.conf
  				$versionsAll = ListVersions(true,true);
  				$versionsMariadb = $versionsAll['mariadb'];
   				if(!in_array($wampConf['mariadbVersion'], $versionsMariadb)) {
  					//MariaDB version in wampmanager.conf does not exist - Correct it
  					$wampIniNewContents['mariadbVersion'] = $versionsMariadb[0];
  					$c_mariadbExe = str_replace($wampConf['mariadbVersion'],$versionsMariadb[0],$c_mariadbExe);
  					$c_mariadbConfFile = str_replace($wampConf['mariadbVersion'],$versionsMariadb[0],$c_mariadbConfFile);
  				}

  				//Check if mariadb service is installed and create it if not
					$command = 'sc query state= all | FIND "SERVICE_NAME: wamp"';
					$output = `$command`;
					if(preg_match("~.*".$c_mariadbService."\s?$~m",$output) === 0) {
						//Service does not exists
							$command = $c_mariadbExe." ".$c_mariadbServiceInstallParams;
							$output = `$command`;
							if(strpos($output, 'successfully installed') === false) {
								$goodParam = false;
  							$error .= "The service '".$c_mariadbService."' seems to be not successfully installed\n";
							}
					}
  			}
  		}
  		if($goodParam) {
				//Check if port is not that used by Mysql or default port in case off and change it if necessary
				$mariaIniFileContents = @file_get_contents($c_mariadbConfFile) or die ("my.ini file not found");
				preg_match_all("~^port[ \t]*=[ \t]*([0-9]{4})\s?$~m",$mariaIniFileContents, $matches);
				if(in_array($c_UsedMysqlPort,$matches[1]) || ($_SERVER['argv'][2] == 'off' && in_array($c_DefaultMysqlPort,$matches[1]))) {
					$portToUse = "3307";
					if($portToUse == $c_UsedMysqlPort)
						$portToUse = "3308";
					$nb_myIni = 0; //must be three replacements: [client], [wampmysqld] and [mysqld] groups
					$findTxtRegex = array(
					'/^(port)[ \t]*=.*$/m',
					);
					$mariaIniFileContents = preg_replace($findTxtRegex,"$1 = ".$portToUse, $mariaIniFileContents, -1, $nb_myIni);
					if($nb_myIni == 3)
						$myIniReplace = true;

					if($myIniReplace) {
						$myIni = fopen($c_mariadbConfFile ,"w");
						fwrite($myIni,$mariaIniFileContents);
						fclose($myIni);
						$myIniConf['mariaPortUsed'] = $portToUse;
						if($portToUse == $c_DefaultMysqlPort)
							$myIniConf['mariaUseOtherPort'] = "off";
						else
							$myIniConf['mariaUseOtherPort'] = "on";
						wampIniSet($configurationFile, $myIniConf);
					}
				}
			}
		}
		else {
			$goodParam = false;
			$error .= $c_mariadbVersionDir." does not exist or is not a directory\n";
		}
		if($goodParam) {
			if($_SERVER['argv'][2] == 'on') {
				//Start mariadb service in case of not started
				$command = 'net start '.$c_mariadbService;
				`$command`;
			}
			elseif($_SERVER['argv'][2] == 'off') {
				//Stop mariadb service in case of started
				$command = 'net stop '.$c_mariadbService;
				`$command`;
				$command = 'sc delete '.$c_mariadbService;
				`$command`;
			}
		}
	}
	elseif($_SERVER['argv'][1] == 'SupportMySQL') {
		if(file_exists($c_mysqlVersionDir) && is_dir($c_mysqlVersionDir)) {
			if($_SERVER['argv'][2] == 'on') {
  			$mysqlVersionList = listDir($c_mysqlVersionDir,'checkMysqlConf','mysql');
  			if(count($mysqlVersionList) == 0) {
  				$goodParam = false;
  				$error .= "No version of MySQL is installed.\n";
  			}
  			else {
  				//Check if mysql version installed is that in wampmanager.conf
  				$versionsAll = ListVersions(true,true);
  				$versionsMySQL = $versionsAll['mysql'];
   				if(!in_array($wampConf['mysqlVersion'], $versionsMySQL)) {
  					//MySQL version in wampmanager.conf does not exist - Correct it
  					$wampIniNewContents['mysqlVersion'] = $versionsMySQL[0];
  					$c_mysqlExe = str_replace($wampConf['mysqlVersion'],$versionsMySQL[0],$c_mysqlExe);
  					$c_mysqlConfFile = str_replace($wampConf['mysqlVersion'],$versionsMySQL[0],$c_mysqlConfFile);
  				}

  				//Check if mysql service is installed and create it if not
					$command = 'sc query state= all | FIND "SERVICE_NAME: wamp"';
					$output = `$command`;
					if(preg_match("~.*".$c_mysqlService."\s?$~m",$output) === 0) {
						//Service does not exists
							$command = $c_mysqlExe." ".$c_mysqlServiceInstallParams;
							$output = `$command`;
							if(strpos($output, 'successfully installed') === false) {
								$goodParam = false;
  							$error .= "The service '".$c_mysqlService."' seems to be not successfully installed\n";
							}
					}
  			}
  		}
  		if($goodParam) {
				//Check if port is not that used by MariaDB and change it if necessary
				$mySqlIniFileContents = @file_get_contents($c_mysqlConfFile) or die ("my.ini file not found");
				preg_match_all("~^port[ \t]*=[ \t]*([0-9]{4})\s?$~m",$mySqlIniFileContents, $matches);
				if(in_array($c_UsedMariaPort,$matches[1]) || ($_SERVER['argv'][2] == 'off' && in_array($c_DefaultMysqlPort,$matches[1]))) {
					$portToUse = "3308";
					if($portToUse == $c_UsedMariaPort)
						$portToUse = "3309";
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
			}
		}
		else {
			$goodParam = false;
			$error .= $c_mysqlVersionDir." does not exist or is not a directory\n";
		}
		if($goodParam) {
			if($_SERVER['argv'][2] == 'on') {
				//Start mysql service in case of not started
				$command = 'net start '.$c_mysqlService;
				`$command`;
			}
			elseif($_SERVER['argv'][2] == 'off') {
				//Stop mysql service in case of started
				$command = 'net stop '.$c_mysqlService;
				`$command`;
				$command = 'sc delete '.$c_mysqlService;
				`$command`;
			}
		}
	}

	if($goodParam) {
		$wampIniNewContents[$_SERVER['argv'][1]] = $_SERVER['argv'][2];
		wampIniSet($configurationFile, $wampIniNewContents);
	}
	else {
		echo "The parameter '".$_SERVER['argv'][1]."' cannot be switched '".$_SERVER['argv'][2]."'\n\n";
		echo $error."\n\n";
		echo "----- Switch canceled -----\n\n";
		echo "\nPress ENTER to continue...";
 		trim(fgets(STDIN));
	}
}
?>
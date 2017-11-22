<?php
// 3.1.0 added $typebase = 'mysql'; for sql-mode

// MySQL submenu
$myPattern = ';WAMPMYSQLMENUSTART';
$myreplace = <<< EOF
;WAMPMYSQLMENUSTART
Type: submenu; Caption: "${w_version}"; SubMenu: mysqlVersion; Glyph: 3
Type: servicesubmenu; Caption: "${w_service} '${c_mysqlService}'"; Service: ${c_mysqlService}; SubMenu: mysqlService
Type: submenu; Caption: "${w_mysqlSettings}"; SubMenu: mysql_params; Glyph: 25
Type: item; Caption: "${w_mysqlConsole}"; Action: run; FileName: "${c_mysqlConsole}"; Parameters: "-u root -p"; Glyph: 0
Type: item; Caption: "my.ini"; Glyph: 33; Action: run; FileName: "${c_editor}"; parameters: "${c_mysqlConfFile}"
Type: item; Caption: "${w_mysqlLog}"; Glyph: 33; Action: run; FileName: "${c_editor}"; parameters: "${c_installDir}/${logDir}mysql.log"
${MysqlTestPortUsed}Type: separator; Caption: "${w_portUsedMysql}${c_UsedMysqlPort}"
${MysqlTestPortUsed}Type: item; Caption: "${w_testPortMysql}"; Action: run; FileName: "${c_phpExe}"; Parameters: "testPort.php 3306 ${c_mysqlService}";WorkingDir: "$c_installDir/scripts"; Flags: waituntilterminated; Glyph: 24
${MysqlTestPortUsed}Type: item; Caption: "${w_AlternateMysqlPort}"; Action: multi; Actions: UseAlternateMysqlPort; Glyph: 24
${MysqlTestPortUsed}Type: item; Caption: "${w_testPortMysqlUsed}${c_UsedMysqlPort}"; Action: run; FileName: "${c_phpExe}"; Parameters: "testPort.php ${c_UsedMysqlPort} ${c_mysqlService}";WorkingDir: "$c_installDir/scripts"; Flags: waituntilterminated; Glyph: 24
Type: item; Caption: "${w_mysqlDoc}"; Action: run; FileName: "${c_navigator}"; Parameters: "${c_edge}http://dev.mysql.com/doc/index.html"; Glyph: 35
EOF;

$tpl = str_replace($myPattern,$myreplace,$tpl);

// *****************
// versions of MySQL
$mysqlVersionList = listDir($c_mysqlVersionDir,'checkMysqlConf','mysql');
// Sort in versions number order
natcasesort($mysqlVersionList);

$myPattern = ';WAMPMYSQLVERSIONSTART';
$myreplace = $myPattern."
";
$myreplacemenu = '';
foreach ($mysqlVersionList as $oneMysql)
{
  $oneMysqlVersion = str_ireplace('mysql','',$oneMysql);
  //File wamp/bin/mysql/mysqlx.y.z/wampserver.conf
  //Check service name if it is modified
  $myConfFile = $c_mysqlVersionDir.'/mysql'.$oneMysqlVersion.'/'.$wampBinConfFiles;
  $mySqlConfContents = file_get_contents($myConfFile);
	if(substr_count($mySqlConfContents, " ".$c_mysqlService."'") < 2) {
		$pattern = array(
			"/^.*mysqlServiceInstallParams.*\n/m",
			"/^.*mysqlServiceRemoveParams.*\n/m");
		$replace = array(
			"\$mysqlConf['mysqlServiceInstallParams'] = '--install-manual ".$c_mysqlService."';\n",
			"\$mysqlConf['mysqlServiceRemoveParams'] = '--remove ".$c_mysqlService."';\n");
		$mySqlConfContents = preg_replace($pattern,$replace,$mySqlConfContents, 1, $count);
		if(!is_null($mySqlConfContents) && $count > 0) {
			$fp = fopen($myConfFile,'w');
			fwrite($fp,$mySqlConfContents);
			fclose($fp);
		}
	}
  unset($mysqlConf);
  include $myConfFile;

	//Check name of the group [wamp...] under '# The MySQL server' in my.ini file
	//    must be the name of the mysql service.
	$myIniFile = $c_mysqlVersionDir.'/mysql'.$oneMysqlVersion.'/'.$mysqlConf['mysqlConfFile'];
	$myIniContents = file_get_contents($myIniFile);

	if(strpos($myIniContents, "[".$c_mysqlService."]") === false) {
		$myIniContents = preg_replace("/^\[wamp.*\].*\n/m", "[".$c_mysqlService."]\r\n", $myIniContents, 1, $count);
		if(!is_null($myIniContents) && $count == 1) {
			$fp = fopen($myIniFile,'w');
			fwrite($fp,$myIniContents);
			fclose($fp);
			$mysqlServer[$oneMysqlVersion] = 0;
		}
		else { //The MySQL server has not the same name as mysql service
			$mysqlServer[$oneMysqlVersion] = -1;
		}
	}
	else
		$mysqlServer[$oneMysqlVersion] = 0;
	unset($myIniContents);

	if ($oneMysqlVersion === $wampConf['mysqlVersion'] && $mysqlServer[$oneMysqlVersion] == 0)
  	$mysqlServer[$oneMysqlVersion] = 1;

	if ($mysqlServer[$oneMysqlVersion] == 1) {
    $myreplace .= 'Type: item; Caption: "'.$oneMysqlVersion.'"; Action: multi; Actions:switchMysql'.$oneMysqlVersion.'; Glyph: 13
';
	}
  elseif($mysqlServer[$oneMysqlVersion] == 0) {
    $myreplace .= 'Type: item; Caption: "'.$oneMysqlVersion.'"; Action: multi; Actions:switchMysql'.$oneMysqlVersion.'
';
  	$myreplacemenu .= <<< EOF
[switchMysql${oneMysqlVersion}]
Action: service; Service: ${c_mysqlService}; ServiceAction: stop; Flags: ignoreerrors waituntilterminated
Action: run; FileName: "${c_mysqlExe}"; Parameters: "${c_mysqlServiceRemoveParams}"; ShowCmd: hidden; Flags: ignoreerrors waituntilterminated
Action: closeservices;
Action: run; FileName: "${c_phpCli}";Parameters: "switchMysqlVersion.php ${oneMysqlVersion}";WorkingDir: "${c_installDir}/scripts"; Flags: waituntilterminated
Action: run; FileName: "${c_mysqlVersionDir}/mysql${oneMysqlVersion}/${mysqlConf['mysqlExeDir']}/${mysqlConf['mysqlExeFile']}"; Parameters: "${mysqlConf['mysqlServiceInstallParams']}"; ShowCmd: hidden; Flags: waituntilterminated
Action: run; FileName: "${c_phpExe}";Parameters: "switchMysqlPort.php ${c_UsedMysqlPort}";WorkingDir: "${c_installDir}/scripts"; Flags: waituntilterminated
Action: run; FileName: "net"; Parameters: "start ${c_mysqlService}"; ShowCmd: hidden; Flags: waituntilterminated
Action: run; FileName: "${c_phpCli}";Parameters: "refresh.php";WorkingDir: "${c_installDir}/scripts"; Flags: waituntilterminated
Action: resetservices;
Action: readconfig;

EOF;
	}
  elseif($mysqlServer[$oneMysqlVersion] == -1) {
    $myreplace .= 'Type: item; Caption: "'.$oneMysqlVersion.'"; Action: multi; Actions:switchMysql'.$oneMysqlVersion.'; Glyph: 19
';
  	$myreplacemenu .= '[switchMysql'.$oneMysqlVersion.']
Action: run; FileName: "'.$c_phpExe.'";Parameters: "msg.php 13 '.base64_encode($myIniFile).' '.base64_encode($c_mysqlService).'";WorkingDir: "'.$c_installDir.'/scripts"; Flags: waituntilterminated
';
	}

}

$tpl = str_replace($myPattern,$myreplace.$myreplacemenu,$tpl);

// **********************
// Configuration of MySQL
// It only retrieves the values of the [wampmysqld] section
$mysqliniS = parse_ini_file($c_mysqlConfFile, true);
$mysqlini = $mysqliniS[$c_mysqlService];

//Check if default sql-mode
if(!array_key_exists('sql-mode', $mysqlini))
	$mysqlini = $mysqlini + array('sql-mode' => 'default');

$myIniFileContents = @file_get_contents($c_mysqlConfFile) or die ("my.ini file not found");
//Check if there is a commented or not user sql-mode
$UserSqlMode = (preg_match('/^[;]?sql-mode[ \t]*=[ \t]*"[^"].*$/m',$myIniFileContents) > 0 ? true : false);
//Check if skip-grant-tables is on (uncommented)
if(preg_match('/^skip-grant-tables[\r]?$/m',$myIniFileContents) > 0) {
	$mysqlini = $mysqlini + array('skip-grant-tables' => 'On - !! WARNING !!');
}
unset($myIniFileContents);

$mysqlErrorMsg = array();
$mysqlParams = array_combine($mysqlParams,$mysqlParams);
foreach($mysqlParams as $next_param_name=>$next_param_text)
{
  if (isset($mysqlini[$next_param_text]))
  {
  	if(array_key_exists($next_param_name, $mysqlParamsNotOnOff)) {
  		if($mysqlParamsNotOnOff[$next_param_name]['change'] !== true) {
  	  	$params_for_mysqlini[$next_param_name] = -2;
  	  	if(!empty($mysqlParamsNotOnOff[$next_param_name]['msg']))
  	  		$mysqlErrorMsg[$next_param_name] = "\n".$mysqlParamsNotOnOff[$next_param_name]['msg']."\n";
   	  	else
					$mysqlErrorMsg[$next_param_name] = "\nThe value of this MySQL parameter must be modified in the file:\n".$c_mysqlConfFile."\nNot to change the wrong file, the best way to access this file is:\nWampmanager icon->MySQL->my.ini\n";
  		}
  		else {
  	  $params_for_mysqlini[$next_param_name] = -3;
  	  if($mysqlParamsNotOnOff[$next_param_name]['title'] == 'Special')
  	  	$params_for_mysqlini[$next_param_name] = -4;
  		}
  	}
  	elseif($mysqlini[$next_param_text] == "Off")
  		$params_for_mysqlini[$next_param_name] = '0';
  	elseif($mysqlini[$next_param_text] == 0)
  		$params_for_mysqlini[$next_param_name] = '0';
  	elseif($mysqlini[$next_param_text] == "On")
  		$params_for_mysqlini[$next_param_name] = '1';
  	elseif($mysqlini[$next_param_text] == 1)
  		$params_for_mysqlini[$next_param_name] = '1';
  	else
  	  $params_for_mysqlini[$next_param_name] = -2;
  }
  else //Parameter in $mysqlParams (config.inc.php) does not exist in my.ini
    $params_for_mysqlini[$next_param_name] = -1;
}

$mysqlConfText = ";WAMPMYSQL_PARAMSSTART
";
$mysqlConfTextInfo = $mysqlConfForInfo = "";
$action_sup = array();
$information_only = false;
foreach ($params_for_mysqlini as $paramname=>$paramstatus)
{
	if($params_for_mysqlini[$paramname] == 0 || $params_for_mysqlini[$paramname] == 1) {
		$glyph = ($params_for_mysqlini[$paramname] == 1 ? '13' : '22');
    $mysqlConfText .= 'Type: item; Caption: "'.$paramname.'"; Glyph: '.$glyph.'; Action: multi; Actions: '.$mysqlParams[$paramname].'
';
	}
	elseif ($params_for_mysqlini[$paramname] == -2) { // I blue to indicate different from 0 or 1 or On or Off
		if(!$information_only) {
			$mysqlConfForInfo .= 'Type: separator; Caption: "'.$w_phpparam_info.'"
';
			$information_only = true;
		}
		$glyph = '22';
		if($paramname == 'skip-grant-tables') {
			$glyph = '19';
			$WarningsAtEnd = true;
			if(!isset($WarningMysql)) {
				$WarningMysql = true;
				$WarningText .= 'Type: separator; Caption: "Warning MySQL"
';
			}
			$WarningText .= 'Type: item; Caption: "'.$paramname.' = '.$mysqlini[$paramname].'"; Glyph: '.$glyph.'; Action: multi; Actions: '.$mysqlParams[$paramname].'
';
		}
     $mysqlConfForInfo .= 'Type: item; Caption: "'.$paramname.' = '.$mysqlini[$paramname].'"; Glyph: '.$glyph.'; Action: multi; Actions: '.$mysqlParams[$paramname].'
';
	}
	elseif ($params_for_mysqlini[$paramname] == -3) { // Indicate different from 0 or 1 or On or Off but can be changed
		$action_sup[] = $paramname;
		$text = ($mysqlParamsNotOnOff[$paramname]['title'] == 'Number' ? ' - '.$mysqlParamsNotOnOff[$paramname]['text'][$mysqlini[$paramname]] : '');
		$mysqlConfText .= 'Type: submenu; Caption: "'.$paramname.' = '.$mysqlini[$paramname].$text.'"; Submenu: '.$paramname.'; Glyph: 9
';
	}
	elseif ($params_for_mysqlini[$paramname] == -4) { // Indicate different from 0 or 1 or On or Off but can be changed with Special treatment
		$action_sup[] = $paramname;
		if($paramname == 'sql-mode') {
			$typebase = 'mysql';
			$mysqlConfTextMode = '';
			$default_modes = array(
				'5.5' => array('NONE'),
				'5.6' => array('NO_ENGINE_SUBSTITUTION'),
				'5.7' => array('ONLY_FULL_GROUP_BY', 'STRICT_TRANS_TABLES', 'NO_ZERO_IN_DATE', 'NO_ZERO_DATE', 'ERROR_FOR_DIVISION_BY_ZERO', 'NO_AUTO_CREATE_USER', 'NO_ENGINE_SUBSTITUTION'),
				'valid' => array('ALLOW_INVALID_DATES','ANSI_QUOTES','ERROR_FOR_DIVISION_BY_ZERO','HIGH_NOT_PRECEDENCE','IGNORE_SPACE','NO_AUTO_CREATE_USER','NO_AUTO_VALUE_ON_ZERO','NO_BACKSLASH_ESCAPES','NO_DIR_IN_CREATE','NO_ENGINE_SUBSTITUTION','NO_FIELD_OPTIONS','NO_KEY_OPTIONS','NO_TABLE_OPTIONS','NO_UNSIGNED_SUBTRACTION','NO_ZERO_DATE','NO_ZERO_IN_DATE','ONLY_FULL_GROUP_BY','PAD_CHAR_TO_FULL_LENGTH','PIPES_AS_CONCAT','REAL_AS_FLOAT','STRICT_ALL_TABLES','STRICT_TRANS_TABLES'),
				);
				//Memorize default values
				if(version_compare($c_mysqlVersion, '5.6', '<'))
					$default_valeurs = $default_modes['5.5'];
				elseif(version_compare($c_mysqlVersion, '5.7', '>='))
					$default_valeurs = $default_modes['5.7'];
				else
					$default_valeurs = $default_modes['5.6'];

			if(empty($mysqlini['sql-mode'])) {
				$valeurs[0] = 'NONE';
				$m_valeur = 'none';
				$mysqlini['sql-mode'] = 'none';
      	$mysqlConfTextInfo .= 'Type: separator; Caption: "sql-mode: '.$w_mysql_none.'"
';
				$mysqlConfTextMode = 'Type: submenu; Caption: "'.$paramname.'"; Submenu: '.$paramname.$typebase.'; Glyph: 9
';
			}
			elseif($mysqlini['sql-mode'] == 'default') {
				$valeurs = $default_valeurs;
      	$mysqlConfTextInfo .= 'Type: separator; Caption: "sql-mode:  '.$w_mysql_default.'"
';
				foreach($valeurs as $val) {
					$mysqlConfTextInfo .= 'Type: item; Caption: "'.$val.'"; Action: multi; Actions: none
';
				}
				$m_valeur = 'default';
				$mysqlConfTextMode = 'Type: submenu; Caption: "'.$paramname.'"; Submenu: '.$paramname.$typebase.'; Glyph: 9
';
			}
			else {
				$valeurs = explode(',',$mysqlini['sql-mode']);
				$valeurs = array_map('trim',$valeurs);
     		$mysqlConfTextInfo .= 'Type: separator; Caption: "sql-mode: '.$w_mysql_user.'"
';
				$MyUserError = false;
				foreach($valeurs as $val) {
					//Check if each user value is allowed
					if(in_array($val,$default_modes['valid'])) {
						$UserGlyph = '';
						$notValid = '';
					}
					else {
						$MyUserError = true;
						$UserGlyph = '; Glyph: 19';
						$notValid = ' - Not valid mode';
					}
					$mysqlConfTextInfo .= 'Type: item; Caption: "'.$val.$notValid.'"; Action: multi; Actions: none'.$UserGlyph.'
';
				}
				$m_valeur = 'user';
				$mysqlini['sql-mode'] = 'user';
				$mysqlConfTextMode = 'Type: submenu; Caption: "'.$paramname.'"; Submenu: '.$paramname.$typebase.'; Glyph: 9
';
			}
			$mysqlConfTextInfo .= $mysqlConfTextMode;
		}
		else {
		$mysqlConfText .= 'Type: submenu; Caption: "'.$paramname.' = '.$mysqlini[$paramname].'"; Submenu: '.$paramname.$typebase.'; Glyph: 9
';
		}
	}
}
//Check for supplemtary actions
$MenuSup = $SubMenuSup = array();
if(count($action_sup) > 0) {
	$i = 0;
	foreach($action_sup as $action) {
		$MenuSup[$i] = $SubMenuSup[$i] = '';
		if($mysqlParamsNotOnOff[$action]['title'] == 'Special') {
			if($action == 'sql-mode') {
				$actionToDo = $actionName = $param_value = array();
				if($mysqlini['sql-mode'] == 'default') {
					if($UserSqlMode) {
						$actionToDo[] = 'user';
						$actionName[] = $w_mysql_user;
						$param_value[] = 'user';
					}
					$actionToDo[] = 'none';
					$actionName[] = $w_mysql_none;
					$param_value[] = 'none';
				}
				elseif($mysqlini['sql-mode'] == 'none') {
					if($UserSqlMode) {
						$actionToDo[] = 'user';
						$actionName[] = $w_mysql_user;
						$param_value[] = 'user';
					}
					$actionToDo[] = 'default';
					$actionName[] = $w_mysql_default;
					$param_value[] = 'default';
				}
				if($mysqlini['sql-mode'] == 'user') {
					$actionToDo[] = 'none';
					$actionName[] = $w_mysql_none;
					$param_value[] = 'none';
					$actionToDo[] = 'default';
					$actionName[] = $w_mysql_default;
					$param_value[] = 'default';
				}
				$MenuSup[$i] .= '[sql-mode'.$typebase.']
Type: separator; Caption: "sql-mode"
';
				for($j = 0 ; $j < count($actionToDo) ; $j++) {
					if($actionToDo[$j] == 'default') {
						$MenuSup[$i] .= <<< EOF

Type: separator; Caption: "MySQL ${c_mysqlVersion}"
Type: separator; Caption: "sql-mode ${actionName[$j]} = "

EOF;
						foreach($default_valeurs as $val) {
						$MenuSup[$i] .= 'Type: item; Caption: "'.$val.'"; Action: multi; Actions: none
';
						}
						$MenuSup[$i] .= 'Type: separator
';
					}
				$MenuSup[$i] .= 'Type: item; Caption: "sql-mode -> '.$actionName[$j].'"; Action: multi; Actions: '.$action.$actionToDo[$j].$typebase.'
';
					$SubMenuSup[$i] .= <<< EOF
[${action}${actionToDo[$j]}${typebase}]
Action: service; Service: ${c_mysqlService}; ServiceAction: stop; Flags: waituntilterminated
Action: run; FileName: "${c_phpExe}";Parameters: "changeMysqlParam.php noquotes ${action} ${param_value[$j]}";WorkingDir: "${c_installDir}/scripts"; Flags: waituntilterminated
Action: run; FileName: "${c_phpCli}";Parameters: "refresh.php";WorkingDir: "${c_installDir}/scripts"; Flags: waituntilterminated
Action: run; FileName: "net"; Parameters: "start ${c_mysqlService}"; ShowCmd: hidden; Flags: waituntilterminated
Action: resetservices
Action: readconfig;

EOF;
				}
			}
		}
		else {
			$MenuSup[$i] .= '['.$action.']
Type: separator; Caption: "'.$mysqlParamsNotOnOff[$action]['title'].'"
';
			$c_values = $mysqlParamsNotOnOff[$action]['values'];
			if($mysqlParamsNotOnOff[$action]['quoted'])
				$quoted = 'quotes';
			else
				$quoted = 'noquotes';
			foreach($c_values as $value) {
				$text = ($mysqlParamsNotOnOff[$action]['title'] == 'Number' ? " - ".$mysqlParamsNotOnOff[$action]['text'][$value] : "");
				$MenuSup[$i] .= 'Type: item; Caption: "'.$value.$text.'"; Action: multi; Actions: '.$action.$value.'
';
				if(strtolower($value) == 'choose') {
					$param_value = '%'.$mysqlParamsNotOnOff[$action]['title'].'%';
					$param_third = ' '.$mysqlParamsNotOnOff[$action]['title'];
					$c_phpRun = $c_phpExe;
				}
				else {
					$param_value = $value;
					$param_third = '';
					$c_phpRun = $c_phpCli;
				}
				$SubMenuSup[$i] .= <<< EOF
[${action}${value}]
Action: service; Service: ${c_mysqlService}; ServiceAction: stop; Flags: waituntilterminated
Action: run; FileName: "${c_phpRun}";Parameters: "changeMysqlParam.php ${quoted} ${action} ${param_value}${param_third}";WorkingDir: "${c_installDir}/scripts"; Flags: waituntilterminated
Action: run; FileName: "${c_phpCli}";Parameters: "refresh.php";WorkingDir: "${c_installDir}/scripts"; Flags: waituntilterminated
Action: run; FileName: "net"; Parameters: "start ${c_mysqlService}"; ShowCmd: hidden; Flags: waituntilterminated
Action: resetservices
Action: readconfig;

EOF;
			}
		}
	$i++;
	}
}
$mysqlConfText .= $mysqlConfTextInfo.$mysqlConfForInfo;

foreach ($params_for_mysqlini as $paramname=>$paramstatus) {
	if ($params_for_mysqlini[$paramname] == 1 || $params_for_mysqlini[$paramname] == 0) {
		$SwitchAction = ($params_for_mysqlini[$paramname] == 1 ? 'off' : 'on');
  	$mysqlConfText .= <<< EOF
[${mysqlParams[$paramname]}]
Action: service; Service: ${c_mysqlService}; ServiceAction: stop; Flags: waituntilterminated
Action: run; FileName: "${c_phpCli}";Parameters: "switchMysqlParam.php ${mysqlParams[$paramname]} ${SwitchAction}";WorkingDir: "${c_installDir}/scripts"; Flags: waituntilterminated
Action: run; FileName: "${c_phpCli}";Parameters: "refresh.php";WorkingDir: "${c_installDir}/scripts"; Flags: waituntilterminated
Action: run; FileName: "net"; Parameters: "start ${c_mysqlService}"; ShowCmd: hidden; Flags: waituntilterminated
Action: resetservices
Action: readconfig;

EOF;
	}
  elseif ($params_for_mysqlini[$paramname] == -2)  {//Parameter is neither 'on' nor 'off'
  	$mysqlConfText .= '['.$mysqlParams[$paramname].']
Action: run; FileName: "'.$c_phpExe.'";Parameters: "msg.php 6 '.base64_encode($paramname).' '.base64_encode($mysqlErrorMsg[$paramname]).'";WorkingDir: "'.$c_installDir.'/scripts"; Flags: waituntilterminated
';
	}
}
if(count($MenuSup) > 0) {
	for($i = 0 ; $i < count($MenuSup); $i++)
		$mysqlConfText .= $MenuSup[$i].$SubMenuSup[$i];
}

$tpl = str_replace(';WAMPMYSQL_PARAMSSTART',$mysqlConfText,$tpl);

?>
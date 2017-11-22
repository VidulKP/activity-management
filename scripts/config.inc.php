<?php
// Update 3.1.0
// Support of sql-mode for MariaDB
//
//
//

$configurationFile = '../wampmanager.conf';
$templateFile = '../wampmanager.tpl';
$wampserverIniFile = '../wampmanager.ini';
$langDir = '../lang/';
$aliasDir = '../alias/';
$modulesDir = 'modules/';
$logDir = 'logs/';
$wampBinConfFiles = 'wampserver.conf';
$phpConfFileForApache = 'phpForApache.ini';

// Loading Wampserver configuration
$wampConf = @parse_ini_file($configurationFile);

//We enter the variables of the template with the local conf
$c_installDir = $wampConf['installDir'];
$wwwDir = $c_installDir.'/www';
$c_wampVersion = $wampConf['wampserverVersion'];
$c_wampMode = $wampConf['wampserverMode'];
$c_navigator = $wampConf['navigator'];
//For Windows 10 and Edge it is not the same as for other browsers
//It is not complete path to browser with parameter http://website/
//but by 'cmd.exe /c "start /b Microsoft-Edge:http://website/"'
$c_edge = "";
if($c_navigator == "Edge") {
	//Check if Windows 10
	if(php_uname('r') < 10) {
		error_log("Edge should be defined as default navigator only with Windows 10");
		if(file_exists("c:/Program Files (x86)/Internet Explorer/iexplore.exe"))
			$c_navigator = "c:/Program Files (x86)/Internet Explorer/iexplore.exe";
		elseif(file_exists("c:/Program Files/Internet Explorer/iexplore.exe"))
			$c_navigator = "c:/Program Files/Internet Explorer/iexplore.exe";
		else
			$c_navigateor = "iexplore.exe";
	}
	else {
	$c_navigator = "cmd.exe";
	$c_edge = "/c start /b Microsoft-Edge:";
	}
}
$c_editor = $wampConf['editor'];

//Variable suppressLocalhost based on urlAddLocalhost
$c_suppressLocalhost = ($wampConf['urlAddLocalhost'] == "off" ? false : true);
//Adding Variables for Ports
$c_DefaultPort = "80";
$c_UsedPort = $wampConf['apachePortUsed'];
$c_DefaultMysqlPort = $wampConf['mysqlDefaultPort'];
$c_UsedMysqlPort = $wampConf['mysqlPortUsed'];
$c_UsedMariaPort = $wampConf['mariaPortUsed'];

//Variables for Apache
$c_apacheService = $wampConf['ServiceApache'];
$c_apacheVersion = $wampConf['apacheVersion'];
$c_apacheServiceInstallParams = $wampConf['apacheServiceInstallParams'];
$c_apacheServiceRemoveParams = $wampConf['apacheServiceRemoveParams'];
$c_apacheVersionDir = $wampConf['installDir'].'/bin/apache';
$c_apacheConfFile = $c_apacheVersionDir.'/apache'.$wampConf['apacheVersion'].'/'.$wampConf['apacheConfDir'].'/'.$wampConf['apacheConfFile'];
$c_apacheVhostConfFile = $c_apacheVersionDir.'/apache'.$wampConf['apacheVersion'].'/'.$wampConf['apacheConfDir'].'/extra/httpd-vhosts.conf';
$c_apacheAutoIndexConfFile = $c_apacheVersionDir.'/apache'.$wampConf['apacheVersion'].'/'.$wampConf['apacheConfDir'].'/extra/httpd-autoindex.conf';
$c_apacheExe = $c_apacheVersionDir.'/apache'.$wampConf['apacheVersion'].'/'.$wampConf['apacheExeDir'].'/'.$wampConf['apacheExeFile'];

//Variables for PHP
$c_phpVersion = $wampConf['phpVersion'];
$c_phpCliVersion = $wampConf['phpCliVersion'];
$c_phpVersionDir = $wampConf['installDir'].'/bin/php';
$c_phpConfFile = $c_apacheVersionDir.'/apache'.$wampConf['apacheVersion'].'/'.$wampConf['apacheExeDir'].'/'.$wampConf['phpConfFile'];
$c_phpCliConfFile = $c_phpVersionDir.'/php'.$c_phpCliVersion.'/'.$wampConf['phpConfFile'];
$c_phpExe = $c_phpVersionDir.'/php'.$c_phpCliVersion.'/'.$wampConf['phpExeFile'];
$c_phpCli = $c_phpVersionDir.'/php'.$c_phpCliVersion.'/'.$wampConf['phpCliFile'];
$phpExtDir = $c_phpVersionDir.'/php'.$wampConf['phpVersion'].'/ext/';

//Variables for MySQL
$c_mysqlService = $wampConf['ServiceMysql'];
$c_mysqlVersion = $wampConf['mysqlVersion'];
$c_mysqlServiceInstallParams = $wampConf['mysqlServiceInstallParams'];
$c_mysqlServiceRemoveParams = $wampConf['mysqlServiceRemoveParams'];
$c_mysqlVersionDir = $wampConf['installDir'].'/bin/mysql';
$c_mysqlExe = $c_mysqlVersionDir.'/mysql'.$wampConf['mysqlVersion'].'/'.$wampConf['mysqlExeDir'].'/'.$wampConf['mysqlExeFile'];
$c_mysqlConfFile = $c_mysqlVersionDir.'/mysql'.$wampConf['mysqlVersion'].'/'.$wampConf['mysqlConfDir'].'/'.$wampConf['mysqlConfFile'];
$c_mysqlConsole = $c_mysqlVersionDir.'/mysql'.$c_mysqlVersion.'/'.$wampConf['mysqlExeDir'].'/mysql.exe';

// Variables for MariaDB
$c_mariadbService = $wampConf['ServiceMariadb'];
$c_mariadbVersion = $wampConf['mariadbVersion'];
$c_mariadbServiceInstallParams = $wampConf['mariadbServiceInstallParams'];
$c_mariadbServiceRemoveParams = $wampConf['mariadbServiceRemoveParams'];
$c_mariadbVersionDir = $wampConf['installDir'].'/bin/mariadb';
$c_mariadbExe = $c_mariadbVersionDir.'/mariadb'.$wampConf['mariadbVersion'].'/'.$wampConf['mariadbExeDir'].'/'.$wampConf['mariadbExeFile'];
$c_mariadbConfFile = $c_mariadbVersionDir.'/mariadb'.$wampConf['mariadbVersion'].'/'.$wampConf['mariadbConfDir'].'/'.$wampConf['mariadbConfFile'];
$c_mariadbConsole = $c_mariadbVersionDir.'/mariadb'.$c_mariadbVersion.'/'.$wampConf['mariadbExeDir'].'/mysql.exe';

// We retrieve the Apache variables (Define)
$c_apacheDefineConf = $c_apacheVersionDir.'/apache'.$wampConf['apacheVersion'].'/wampdefineapache.conf';
if(file_exists($c_apacheDefineConf)) {
	$c_ApacheDefine = @parse_ini_file($c_apacheDefineConf);
}
else
	$c_ApacheDefine = array();

//Check hosts file
$c_hostsFile = getenv('WINDIR').'\system32\drivers\etc\hosts';
$c_hostsFile_writable = true;
if(file_exists($c_hostsFile)) {
	if(!is_file($c_hostsFile))
		error_log($c_hostsFile." is not a file");
	if(!is_writable($c_hostsFile)) {
		if(chmod($c_hostsFile, 0644) === false)
			error_log("Impossible to modify the file ".$c_hostsFile." to be writable");
		if(!is_writable($c_hostsFile)) {
			error_log("The file ".$c_hostsFile." is not writable");
			$c_hostsFile_writable = false;
		}
	}
}
else {
	error_log("The file ".$c_hostsFile." does not exists");
	$c_hostsFile_writable = false;
}


//dll to create symbolic links from php to apache/bin
// // Versions of ICU are 38, 40, 42, 44, 46, 48 to 57
$icu = array(
	'number' => array('57', '56', '55', '54', '53', '52', '51', '50', '49', '48', '46', '44', '42', '40', '38'),
	'name' => array('icudt', 'icuin', 'icuio', 'icule', 'iculx', 'icutest', 'icutu', 'icuuc'),
	);
$php_icu_dll = array();
foreach($icu['number'] as $icu_number) {
	foreach($icu['name'] as $icu_name) {
		$php_icu_dll[] = $icu_name.$icu_number.".dll";
	}
}

$phpDllToCopy = array_merge(
	$php_icu_dll,
	array (
	'libmysql.dll',
	'libeay32.dll',
	'libsasl.dll',
	'libpq.dll',
	'libssh2.dll', //For php 5.5.17
	'php5isapi.dll',
	'php5nsapi.dll',
	'ssleay32.dll',
	'php5ts.dll',
	'php7ts.dll', //For PHP 7
	)
);

//Values must be the same as in php.ini - xdebug parameters must be the latest
$phpParams = array (
	'allow_url_fopen',
	'allow_url_include',
	'asp_tags',
	'auto_globals_jit',
	'date.timezone',
	'default_charset',
	'display_errors',
	'display_startup_errors',
	'expose_php',
	'file_uploads',
	'ignore_repeated_errors',
	'ignore_repeated_source',
	'implicit_flush',
	'intl.default_locale',
	'log_errors',
	'max_execution_time',
	'max_input_time',
	'max_input_vars',
	'memory_limit',
	'output_buffering',
	'post_max_size',
	'register_argc_argv',
	'report_memleaks',
	'short_open_tag',
	'track_errors',
	'upload_max_filesize',
	'zend.enable_gc',
	'zlib_output_compression',
	'xdebug.profiler_enable_trigger',
	'xdebug.profiler_enable',
	'xdebug.remote_enable',
	'xdebug.overload_var_dump',
	);

//PHP parameters with values not On or Off cannot be switched on or off
//Can be changed if 'change' = true && 'title' && 'values'
//Parameter name must be also into $phpParams array
//To manualy enter value, 'Choose' must be the last 'values' and 'title' must be 'Size' or 'Seconds' or 'Integer'
//Warning : specific treatment for date.timezone - Don't modify.
$phpParamsNotOnOff = array(
	'date.timezone' => array(
		'change' => true,
		'title' => 'Timezone',
		'quoted' => true,
		'values' => array('Africa', 'America', 'Antarctica', 'Arctic', 'Asia', 'Atlantic', 'Australia', 'Europe', 'Indian', 'Pacific'),
		),
	'default_charset' => array('change' => false),
	'memory_limit' => array(
		'change' => true,
		'title' => 'Size',
		'quoted' => false,
		'values' => array('16M', '32M', '64M', '128M', '256M', '512M', '1G', 'Choose'),
		),
	'output_buffering' => array('change' => false),
	'max_execution_time' => array(
		'change' => true,
		'title' => 'Seconds',
		'quoted' => false,
		'values' => array('20', '30', '60', '120', '180', '240', '300', 'Choose'),
		),
	'max_input_time' => array(
		'change' => true,
		'title' => 'Seconds',
		'quoted' => false,
		'values' => array('20', '30', '60', '120', '180', '240', '300', 'Choose'),
		),
	'max_input_vars' => array(
		'change' => true,
		'title' => 'Integer',
		'quoted' => false,
		'values' => array('1000', '2000', '2500', '5000', '10000', 'Choose'),
		'min' => '1000',
		'max' => '20000',
		'default' => '2500',
		),
	'post_max_size' => array(
		'change' => true,
		'title' => 'Size',
		'quoted' => false,
		'values' => array('2M', '4M', '8M', '16M','32M', '64M', '128M', '256M', 'Choose'),
		),
	'upload_max_filesize' => array(
		'change' => true,
		'title' => 'Size',
		'quoted' => false,
		'values' => array('2M', '4M', '8M', '16M','32M', '64M', '128M', '256M', 'Choose'),
		),
	'xdebug.overload_var_dump' => array('change' => false),
);
//Parameters to be changed into php.ini CLI the same way as for php.ini
$phpCLIparams = array(
	'date.timezone',
);

// Extensions can not be loaded by extension =
// for example zend_extension
$phpNotLoadExt = array(
	'php_opcache',
	'php_xdebug',
	);

$zend_extensions = array(
	'php_opcache' => array('loaded' => '0','content' => '', 'version' => ''),
	'php_xdebug' => array('loaded' => '0','content' =>'', 'version' => ''),
	);

//MySQL parameters
$mysqlParams = array (
	'basedir',
	'datadir',
	'key_buffer_size',
	'lc-messages',
	'log_error_verbosity',
	'max_allowed_packet',
	'innodb_lock_wait_timeout',
	'sql-mode',
	'sort_buffer_size',
	'skip-grant-tables',
);
//MySQL parameters with values not On or Off cannot be switched on or off
//Can be changed if 'change' = true && 'title' && 'values'
//Parameter name must be also into $mysqlParams array
//To manualy enter value, 'Choose' must be the last 'values' and 'title' must be 'Size' or 'Seconds' or 'Number'
$mysqlParamsNotOnOff = array(
	'basedir' => array(
		'change' => false,
		'msg' => "\nThis setting should not be changed, otherwise you risk losing your existing databases.\n",
		),
	'datadir' => array(
		'change' => false,
		'msg' => "\nThis setting should not be changed, otherwise you risk losing your existing databases.\n",
		),
	'key_buffer_size' => array(
		'change' => true,
		'title' => 'Size',
		'quoted' => false,
		'values' => array('16M', '32M', '64M', 'Choose'),
		),
	'lc-messages' => array(
		'change' => false,
		'msg' => "\nTo set the Error Message Language see:\n\nhttp://dev.mysql.com/doc/refman/5.7/en/error-message-language.html\n",
		),
	'log_error_verbosity' => array(
		'change' => true,
		'title' => 'Number',
		'quoted' => false,
		'values' => array('1', '2', '3'),
		'text' => array('1' => 'Errors only', '2' => 'Errors and warnings', '3' => 'Errors, warnings, and notes'),
		),
	'max_allowed_packet' => array(
		'change' => true,
		'title' => 'Size',
		'quoted' => false,
		'values' => array('16M', '32M', '64M', 'Choose'),
		),
	'innodb_lock_wait_timeout' => array(
		'change' => true,
		'title' => 'Seconds',
		'quoted' => false,
		'values' => array('20', '30', '50', '120', 'Choose'),
		),
	'sql-mode' => array(
		'change' => true,
		'title' => 'Special',
		'quoted' => 'true',
		),
	'sort_buffer_size' => array(
		'change' => true,
		'title' => 'Size',
		'quoted' => false,
		'values' => array('2M', '4M', '16M', 'Choose'),
		),
	'skip-grant-tables' => array(
		'change' => false,
		'msg' => "\n\nWARNING!! WARNING!!\nThis option causes the server to start without using the privilege system at all, WHICH GIVES ANYONE WITH ACCESS TO THE SERVER UNRESTRICTED ACCESS TO ALL DATABASES.\nThis option also causes the server to suppress during its startup sequence the loading of user-defined functions (UDFs), scheduled events, and plugins that were installed.\n\nYou should leave this option 'uncommented' ONLY for the time required to perform certain operations such as the replacement of a lost password for 'root'.\n",
		),
);

//MariaDB parameters
$mariadbParams = array (
	'basedir',
	'datadir',
	'key_buffer_size',
	'lc-messages',
	'max_allowed_packet',
	'innodb_lock_wait_timeout',
	'sql-mode',
	'sort_buffer_size',
	'skip-grant-tables',
);
//MariaDB parameters with values not On or Off cannot be switched on or off
//Can be changed if 'change' = true && 'title' && 'values'
//Parameter name must be also into $mariadbParams array
//To manualy enter value, 'Choose' must be the last 'values' and 'title' must be 'Size' or 'Seconds' or 'Number'
$mariadbParamsNotOnOff = array(
	'basedir' => array(
		'change' => false,
		'msg' => "\nThis setting should not be changed, otherwise you risk losing your existing databases.\n",
		),
	'datadir' => array(
		'change' => false,
		'msg' => "\nThis setting should not be changed, otherwise you risk losing your existing databases.\n",
		),
	'key_buffer_size' => array(
		'change' => true,
		'title' => 'Size',
		'quoted' => false,
		'values' => array('16M', '32M', '64M', 'Choose'),
		),
	'lc-messages' => array(
		'change' => false,
		'msg' => "\nTo set the Error Message Language see:\n\nhttps://mariadb.com/kb/en/mariadb/server-system-variables/#lc_messages\n",
		),
	'max_allowed_packet' => array(
		'change' => true,
		'title' => 'Size',
		'quoted' => false,
		'values' => array('16M', '32M', '64M', 'Choose'),
		),
	'innodb_lock_wait_timeout' => array(
		'change' => true,
		'title' => 'Seconds',
		'quoted' => false,
		'values' => array('20', '30', '50', '120', 'Choose'),
		),
	'sql-mode' => array(
		'change' => true,
		'title' => 'Special',
		'quoted' => 'true',
		),
	'sort_buffer_size' => array(
		'change' => true,
		'title' => 'Size',
		'quoted' => false,
		'values' => array('2M', '4M', '16M', 'Choose'),
		),
	'skip-grant-tables' => array(
		'change' => false,
		'msg' => "\n\nWARNING!! WARNING!!\nThis option causes the server to start without using the privilege system at all, WHICH GIVES ANYONE WITH ACCESS TO THE SERVER UNRESTRICTED ACCESS TO ALL DATABASES.\nThis option also causes the server to suppress during its startup sequence the loading of user-defined functions (UDFs), scheduled events, and plugins that were installed.\n\nYou should leave this option 'uncommented' ONLY for the time required to perform certain operations such as the replacement of a lost password for 'root'.\n",
		),
);

// Adding parameters to WampServer modifiable
// by "Settings" sub-menu on right-click Wampmanager icon
// Needs $w_settings['parameter'] in wamp\lang\modules\settings_english.php
$wamp_Param = array(
	'VirtualHostSubMenu',
	'AliasSubmenu',
	'ProjectSubMenu',
	'NotCheckVirtualHost',
	'NotCheckDuplicate',
	'VhostAllLocalIp',
	'SupportMySQL',
	'SupportMariaDB',
	'HomepageAtStartup',
	'##DaredevilOptions',
	'MenuItemOnline',
	'ItemServicesNames',
	'urlAddLocalhost',
	);
	//Wampserver parameters be switched by php.exe and not php-win.exe
	$wamp_ParamPhpExe = array(
		'SupportMariaDB',
		'SupportMySQL',
	);


// Apache modules which should not be disabled
$apacheModNotDisable = array(
	'authz_core_module',
	'authz_host_module',
	'php5_module',
	'php7_module',
	);

?>

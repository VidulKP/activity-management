<?php
//3.0.7
require 'wampserver.lib.php';
require 'config.inc.php';

$newMariaDBVersion = $_SERVER['argv'][1];

//on charge le fichier de conf de la nouvelle version
require $c_mariadbVersionDir.'/mariadb'.$newMariaDBVersion.'/'.$wampBinConfFiles;

$mariadbConf['mariadbVersion'] = $newMariaDBVersion;

wampIniSet($configurationFile, $mariadbConf);
?>
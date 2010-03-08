<?php

// Define path to application directory
defined('AP') || define('AP', realpath(dirname(__FILE__)));

// Define application environment
defined('AE') || define('AE', (getenv('AE') ? getenv('AE') : 'production'));

// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
    realpath(AP . '/../library'),
    get_include_path(),
)));

/** Zend_Application */
require_once 'Zend/Application.php';
require_once 'Zend/Config/Ini.php';

$config = new Zend_Config_Ini(AP . '/configs/application.ini', AE, array('allowModifications' => true));

if (file_exists(AP . '/configs/local.ini')) {
    $configLocal = new Zend_Config_Ini(AP . '/configs/local.ini', AE);
    $config = $config->merge($configLocal);
}

// Create application, bootstrap, and run
$application = new Zend_Application(AE, $config);
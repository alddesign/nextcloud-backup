<?php
declare(strict_types = 1);
error_reporting(E_ERROR);
@ini_set('display_errors', 'off');
@ini_set('max_execution_time', 3600); //You might want to adjust this to max. value allowd by your hosting provider
@ini_set('memory_limit', '64M'); //The script doesnt need that much

/** @var Backup  */
$currentBackup = null;

//Register error handling as soon as possible
require_once(__DIR__ . '/ErrorHandler.php');
register_shutdown_function('onShutdown');
set_error_handler('onError', E_ERROR);
set_exception_handler('onException');

require_once(__DIR__ . '/Target.class.php');
require_once(__DIR__ . '/Backup.class.php');
require_once(__DIR__ . '/RequestHandler.class.php');
require_once(__DIR__ . '/config.php');

//Start the real work
RequestHandler::start();
echo 'nextcloud-backup-successful';

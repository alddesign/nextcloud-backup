<?php
declare(strict_types = 1);
error_reporting(E_ERROR);
@ini_set('display_errors', 'off');
@ini_set('max_execution_time', '3600'); //You might want to adjust this to max. value allowd by your hosting provider
@ini_set('memory_limit', '64M'); //The script doesnt need that much

require_once(__DIR__ . '/ErrorHandler.php');
require_once(__DIR__ . '/Target.class.php');
require_once(__DIR__ . '/Backup.class.php');
require_once(__DIR__ . '/RequestHandler.class.php');
require_once(__DIR__ . '/config.php');

RequestHandler::start();
echo 'nextcloud-backup-successful';

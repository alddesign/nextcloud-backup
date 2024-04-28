<?php
declare(strict_types = 1);
error_reporting(E_ERROR);
@ini_set('display_errors', 'on');
@ini_set('max_execution_time', '3600');
@ini_set('memory_limit', '512M');

require_once(__DIR__ . '/Target.class.php');
require_once(__DIR__ . '/Backup.class.php');
require_once(__DIR__ . '/config.php');

//Run precheck
$key = $_GET['key'] ?? false;
$targetName = $_GET['target'] ?? false;
$all = isset($_GET['all']) ? $_GET['all'] === '1' : false;
if(!$key || $key !== KEY || (!$targetName && !$all))
{
    die;
}

//Run Backup(s)
$names = $all ? array_keys(TARGETS) : [$targetName];
$total = count($names);
$no = 0;
foreach($names as $name)
{
    if(!isset(TARGETS[$name]))
    {
        http_response_code(500);
        echo sprintf('ERROR: Invalid target name "%s"', $name);
        die;
    }


    $no++;
    $backup = new Backup(TARGETS[$name], $name, $no, $total);
    $backup->run();
}

echo '1';
<?php
declare(strict_types = 1);

function onError(int $errNo, string $errMsg, string $file, int $line)
{
    $type = sprintf('[PHP-ERROR [%s]]', $errNo);
    $message = sprintf('%s - %s:%s', $errMsg, $file, $line);

    handleError($type, $message);
}

function onException(Exception $ex)
{
    $type = '[PHP-EXCEPTION]';
    $message = sprintf('%s%s%s', $ex->getMessage(), "\n", $ex->getTraceAsString());

    handleError($type, $message);
}

/**
 * Handle fatal errors
 */
function onShutdown()
{
    //Holds the last error (that caused the shutdown) or is NULL if there was no error
    $error = error_get_last();
    $errorTypesToHandle = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];
    if(!$error || !in_array($error['type'], $errorTypesToHandle , true))
    {
        return;
    }

    $type = sprintf('[PHP-FATAL-ERROR [%s]]', $error['type']);
    $message = sprintf('%s - %s:%s', $error['message'], $error['file'], $error['line']);

    handleError($type, $message);
}

function handleError(string $type, string $message)
{
    global $currentBackup;

    if(!headers_sent())
    {
        http_response_code(500);
    }

    //$message = sprintf('[PHP-ERROR]: %s - %s:%s', $error['message'], $error['file'], $error['line']);
    $message = sprintf('[%s]: %s', $type, $message);
    
    //Try to write to the backup log file
    if($currentBackup != null)
    {
        $currentBackup->log($message);
    }

    echo $message;
    die;
}
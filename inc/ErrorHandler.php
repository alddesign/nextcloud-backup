<?php
declare(strict_types = 1);

function onError(int $errNo, string $errMsg, string $file, int $line)
{
    $message = sprintf('%s - %s:%s', $errMsg, $file, $line);

    handleError(errConstName($errNo), $message);
}

function onException(Throwable $e)
{
    $message = sprintf('%s%s%s', $e->getMessage(), "\n", $e->getTraceAsString());

    handleError(get_class($e), $message);
}

/**
 * Handle fatal errors
 */
function onShutdown()
{
    //Holds the last error (that caused the shutdown) or is NULL if there was no error
    $error = error_get_last();
    $toHandle = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];
    if(!$error || !in_array($error['type'], $toHandle , true))
    {
        return;
    }

    $message = sprintf('%s - %s:%s', $error['message'], $error['file'], $error['line']);

    handleError(errConstName($error['type']), $message);
}

function handleError(string $type, string $message)
{
    global $currentBackup;

    if(!headers_sent())
    {
        http_response_code(500);
    }

    $errorMsg = sprintf('[%s]: %s', $type, $message);
    
    //Try to write to the backup log file
    $written = false;
    if(!$written && $currentBackup)
    {
        try
        { 
            if($currentBackup->log($errorMsg))
                $written = true; 
        }
        catch(Throwable $e){}
    }

    //Try to write to BACKUP_DIR
    if(!$written)
    {
        try
        {
            $outDir =  match(true)
            {
                defined('BACKUP_DIR') && is_dir(BACKUP_DIR) => rtrim(BACKUP_DIR, '/\\'),
                default => ''
            };

            if($outDir && file_put_contents($outDir . '/errors.log', $errorMsg))
                $written = true;
        }
        catch(Throwable $e){}
    }

    echo $errorMsg;
    die;
}

function errConstName(int $value)
{
    switch ($value) 
    {
        case 1:     return 'E_ERROR';
        case 2:     return 'E_WARNING';
        case 4:     return 'E_PARSE';
        case 8:     return 'E_NOTICE';
        case 16:    return 'E_CORE_ERROR';
        case 32:    return 'E_CORE_WARNING';
        case 64:    return 'E_COMPILE_ERROR';
        case 128:   return 'E_COMPILE_WARNING';
        case 256:   return 'E_USER_ERROR';
        case 512:   return 'E_USER_WARNING';
        case 1024:  return 'E_USER_NOTICE';
        case 2048:  return 'E_STRICT';
        case 4096:  return 'E_RECOVERABLE_ERROR';
        case 8192:  return 'E_DEPRECATED';
        case 16384: return 'E_USER_DEPRECATED';
    }

    return 'UNKNOWN_ERROR';
}
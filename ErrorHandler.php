<?php
register_shutdown_function('onShutdown');

function onShutdown()
{
    //Holds the last error (that caused the shutdown) or is NULL if there was no error
    $error = error_get_last();
    $errorTypesToHandle = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_RECOVERABLE_ERROR];
    if(!$error || !in_array($error['type'], $errorTypesToHandle , true))
    {
        return;
    }

    if(!headers_sent())
    {
        http_response_code(500);
    }

    $message = sprintf('[PHP-ERROR]: %s - %s:%s', $error['message'], $error['file'], $error['line']);
    echo $message;
}

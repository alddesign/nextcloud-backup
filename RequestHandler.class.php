<?php
declare(strict_types = 1);

class RequestHandler
{
    private static string $targetName = '';
    private static bool $all = false;

    private function __construct()
    {
    }

    public static function start()
    {
        try
        {
            trigger_error("Nigga", E_USER_WARNING);
            self::validateRequest();
            self::runBackups();
        }
        catch(Exception $ex)
        {
            self::error($ex->getMessage());
        }
    }

    private static function runBackups()
    {
        $names = self::$all ? array_keys(TARGETS) : [self::$targetName];
        $total = count($names);
        $no = 0;
        foreach($names as $name)
        {
            $no++;
            $backup = new Backup(TARGETS[$name], $name, $no, $total);
            $backup->run();
        }
    }

    /** Validate GET request and store relevant request data */
    private static function validateRequest()
    {
        $key = $_GET['key'] ?? false;
        if(!$key || $key !== KEY)
        {
            throw new Exception('Invalid key');
        }

        self::$targetName = $_GET['target'] ?? '';
        self::$all = isset($_GET['all']) ? $_GET['all'] === '1' : false;
        if(!self::$all && (!self::$targetName || !isset(TARGETS[self::$targetName])))
        {
            throw new Exception(sprintf('Invalid target "%s"', self::$targetName));
        }
    }

    /** Sending error response */
    public static function error(string $message, bool $die = true)
    {
        http_response_code(500);
        echo sprintf("[ERROR]: $message");
        
        if($die)
        {
            die;
        }
    }
}
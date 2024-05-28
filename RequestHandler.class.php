<?php
declare(strict_types = 1);

class RequestHandler
{

    private function __construct()
    {
    }

    public static function start()
    {
        $targets = self::validateRequest();
        self::runBackups($targets);
    }

    /** @param Target[] $targets */
    private static function runBackups(array $targets)
    {
        global $currentBackup;

        $no = 0;
        $total = count($targets);
        foreach($targets as $target)
        {
            $no++;
            $backup = new Backup($target, $no, $total);
            $currentBackup = $backup;
            $backup->run();
            $currentBackup = null;
        }
    }

    /** 
     * Validate GET request and store relevant request data 
     * @return Target[] 
     */
    private static function validateRequest()
    {
        //Check key
        $key = $_GET['key'] ?? false;
        if(!$key || $key !== KEY)
        {
            throw new Exception('Invalid key');
        }

        //Check params
        $targetName = $_GET['target'] ?? '';
        $all = isset($_GET['all']) ? $_GET['all'] === '1' : false;
        if(!$all && !$targetName)
        {
            throw new Exception('Invalid request. Please specify either "target" or "all=1"');
        }

        //Build targets
        if(empty(TARGETS) || !is_array(TARGETS))
        {
            throw new Exception('Invalid configuration: TARGETS');
        }
        if(!$all && !isset(TARGETS[$targetName]))
        {
            throw new Exception(sprintf('Invalid target "%s"', $targetName));
        }

        /** @var Target[] */
        $targets = [];
        /** @var array<string,array> */
        $targetsRaw = $all ? TARGETS : [TARGETS[$targetName]];
        foreach($targetsRaw as $name => $targetRaw)
        {
            $targets[] = new Target($name, $targetRaw);
        }

        return $targets;
    }
}
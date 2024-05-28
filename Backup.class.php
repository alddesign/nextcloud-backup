<?php
declare(strict_types = 1);

class Backup
{
    private Target $target;

    private float $backupTimestamp = 0;
    private string $backupTimeString = '';

    private int $no = 0;
    private int $total = 0;

    private string $nextCloudConfigPhpPath = '';
    private string $baseFilename = '';
    private string $logFilePath = '';
    private string $sqlBackupFilePath = '';
    private string $dataBackupFilePath = '';

    private int $totalBackupSizeByte = 0;

    private const DATETIME_FOMRAT = 'Y-m-d_H-i-s';

    public function __construct(Target $target, int $no, int $total)
    {    
        $this->target = $target;
        $this->no = $no;
        $this->total = $total;
    }

    public function run()
    {
        $this->init();
        $this->deleteOldBackups();

        $this->maintenanceMode(true);

        $this->backupDb();
        $this->backupData();

        $this->maintenanceMode(false);
        $this->log(sprintf('Total backup size: %s', $this->formatBytes($this->totalBackupSizeByte)));
        $this->log(sprintf('Total backup duration: %s sec.', time() - $this->backupTimestamp));
        $this->log('### BACKUP FINISHED ###');
    }

    private function init()
    {
        $time = new DateTime();
        $this->backupTimestamp = $time->getTimestamp();
        $this->backupTimeString = $time->format(self::DATETIME_FOMRAT);

        $this->baseFilename = sprintf('%s___%s', $this->backupTimeString, $this->target->name);
        $this->logFilePath = sprintf('%s/%s%s', $this->target->backupDir, $this->baseFilename, '.log');
        $this->sqlBackupFilePath = sprintf('%s/%s%s', $this->target->backupDir, $this->baseFilename, '.sql');
        $this->dataBackupFilePath = sprintf('%s/%s%s', $this->target->backupDir, $this->baseFilename, '.zip');
        $this->nextCloudConfigPhpPath = sprintf('%s/config/config.php', $this->target->path);

        $data = 
        [
            'version' => APP_VERSION,
            'targetName' => $this->target->name,
            'nextcloudDir' => $this->target->path,
            'nextCloudConfigPhpPath' => $this->nextCloudConfigPhpPath,
            'backupDir' => $this->target->backupDir,
            'baseFilename' => $this->baseFilename,
            'logFilePath' => $this->logFilePath,
            'sqlBackupFilePath' => $this->sqlBackupFilePath,
            'dataBackupFilePath' => $this->dataBackupFilePath,
            'backupTimeString' => $this->backupTimeString,
            'backupTimestamp' => $this->backupTimestamp,
            'phpVersion' => PHP_VERSION,
            'phpMaxExecutionTime' => ini_get('max_execution_time'),
            'phpMemoryLimit' => ini_get('memory_limit'),
        ];

        $this->log(json_encode($data), false);
        $this->log(sprintf('### alddesign/nextcloud-backup %s ###', APP_VERSION));
        $this->log(sprintf('### STARTING BACKUP %s of %s ###', $this->no, $this->total));

        if(!file_exists($this->target->backupDir))
        {
            throw new Exception('Backup directory not found - check path');
        }
        if(!file_exists($this->nextCloudConfigPhpPath))
        {
            throw new Exception('Nextclouds config.php not found - check path');
        }

        $this->log(sprintf('Target name: "%s"', $this->target->name));
        $this->log(sprintf('Nextcloud version of target: %s', $this->getNexcloudVersion()));
    }

    private function deleteOldBackups()
    {
        $keep = $this->target->backupsToKeep;
        if($keep <= 0)
        {
            $this->log('No old backups to deleted. Number of backups to keep is 0 (keep all)');
            return;
        }

        //Find all the .log file & parase the first line line json data
        /** @var string[] */
        $backups = [];
        $filenames = scandir($this->target->backupDir);
        foreach($filenames as $name)
        {
            if(str_ends_with($name, '.log') && str_contains($name, sprintf('___%s', $this->target->name)))
            {
                $f = fopen(sprintf('%s/%s', $this->target->backupDir, $name), 'r');
                $line = !feof($f) ? fgets($f) : '';
                fclose($f);

                $data = json_decode($line, true);
                if(is_array($data) && isset($data['targetName']) && $data['targetName'] === $this->target->name)
                {
                    $backups[(int)$data['backupTimestamp']] = $data;
                }
            }
        }

        //Check
        $found = count($backups); //This includes the current backup because the logfile already exists!
        if($found <= $keep)
        {
            $this->log(sprintf('No old backups to delete. Backups to keep: %s. Backups (including this) found: %s', $keep, $found));
            return;
        }

        $toDelet = $found - $keep;
        $this->log(sprintf('%s backup(s) will be be deleted. Backups to keep: %s. Backups (including this) found: %s', $toDelet, $keep, $found));

        //Delete loop
        $no = 0;
        sort($backups, SORT_ASC); //Sort by timestamp, oldest (smallest) first
        foreach($backups as $data)
        {
            $no++;
            if($no > $toDelet)
            {
                break;
            }

            $this->log(sprintf('Deleting backup %s of %s. Backup datetime %s:', $no, $toDelet, $data['backupTimeString']));
            $this->deleteBackupFile($data['logFilePath']);
            $this->deleteBackupFile($data['sqlBackupFilePath']);
            $this->deleteBackupFile($data['dataBackupFilePath']);
        }

        $this->log('Finished deleting old backups');
    }

    private function deleteBackupFile(string $path)
    {
        if(!file_exists($path))
        {
            $this->logWarning(sprintf('Could not delete backup file "%s". File not found', $path));
            return;
        }

        if(!is_writable($path))
        {
            $this->logWarning(sprintf('Could not delete backup file "%s". File not writeable', $path));
            return;
        }

        if(!unlink($path))
        {
            $this->logWarning(sprintf('Could not delete backup file "%s".', $path));
            return;
        }

        $this->log(sprintf('Deleted backup file "%s"', $path));
    }

    private function backupData()
    {
        $this->log(sprintf('Starting data backup to: %s', $this->dataBackupFilePath));
        $this->log(sprintf('Backing up nextclound directory: %s', $this->target->path));

        // Execute the shell command
        $command = sprintf('cd %s && zip -r %s ./', $this->target->path, $this->dataBackupFilePath);
        exec($command, $output, $result);
        
        if($result !== 0)
        {
            $output = implode(";\n", $output);
            throw new Exception(sprintf('zip result code: %s, %s', $result, $output));
        }

        $size = filesize($this->dataBackupFilePath);
        $this->totalBackupSizeByte += $size;

        $this->log(sprintf('Data backup (%s) finished: %s', $this->formatBytes($size), $this->dataBackupFilePath));
    }    

    private function backupDb()
    {
        $t = $this->target;

        $this->log(sprintf('Starting DB backup to: %s', $this->sqlBackupFilePath));
        $this->log(sprintf('Backing up nextcloud DB: Host: "%s", Name: "%s", User: "%s"', $t->dbHost, $t->dbName, $t->dbUser));

        //Executing shell command
        $command = sprintf('mysqldump --single-transaction -h %s -u %s -p%s %s > %s', $t->dbHost, $t->dbUser, $t->dbPassword, $t->dbName, $this->sqlBackupFilePath);
        exec($command, $output, $result);
        
        if($result !== 0)
        {
            $output = implode(";\n", $output);
            throw new Exception(sprintf('mysqldump result code: %s, %s', $result, $output));
        }

        $size = filesize($this->sqlBackupFilePath);
        $this->totalBackupSizeByte += $size;

        $this->log(sprintf('DB backup (%s) finished: %s', $this->formatBytes($size), $this->sqlBackupFilePath));
    }

    private function getNexcloudVersion()
    {
        require $this->nextCloudConfigPhpPath; //Now we should have as $CONFIG
        
        $version = $CONFIG['version'] ?? 'unknown';
        unset($CONFIG);
        
        return $version;
    }

    private function maintenanceMode(bool $on)
    {
        $line = sprintf('%s$CONFIG["maintenance"] = true; /*added by nextcloud-backup*/', "\n");

        //Get content and remove our line first
        $content = file_get_contents($this->nextCloudConfigPhpPath);
        $content = str_replace($line, '', $content);
        
        if($on)
        {
            //Add line and write file
            $content .= $line;
            $content = file_put_contents($this->nextCloudConfigPhpPath, $content);
            $this->log(sprintf('Enabled maintenance mode - waiting %s seconds', $this->target->maintainWait));
            sleep($this->target->maintainWait);
        }
        else
        {
            //Just write file
            $content = file_put_contents($this->nextCloudConfigPhpPath, $content);
            $this->log('Disabled maintenance mode');
        }
    }

    public function log(string $message, bool $time = true)
    {
        if($time)
        {
            $line = sprintf('%s: %s%s', (new DateTime())->format(self::DATETIME_FOMRAT), $message, "\n");
        }
        else
        {
            $line = sprintf('%s%s', $message, "\n");
        }

        file_put_contents($this->logFilePath, $line, FILE_APPEND | LOCK_EX);
    }

    private function logWarning(string $message)
    {
        $this->log(sprintf('[WARNING]: %s', $message));
    }

    //Format by
    private function formatBytes(int $bytes, int $precision = 2) 
    { 
        $units = ['B', 'KiB', 'MiB', 'GiB', 'TiB']; 
       
        $bytes = max($bytes, 0); 
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
        $pow = min($pow, count($units) - 1); 
       
        $bytes /= pow(1024, $pow);
       
        return round($bytes, $precision) . ' ' . $units[$pow]; 
    } 

}

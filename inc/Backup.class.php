<?php
declare(strict_types = 1);

class Backup
{
    private Target $target;

    private float $backupStartTimestamp = 0;
    private string $backupStartTimeString = '';

    private int $no = 0;
    private int $total = 0;

    private array $nextCloudConfig = [];
    private string $nextCloudConfigPhpPath = '';
    private string $baseFilename = '';
    private string $logFilePath = '';
    private string $sqlBackupFilePath = '';
    private string $ncDirBackupFilePath = '';
    private string $dataDirBackupFilePath = '';

    private int $totalBackupSizeByte = 0;
    private int $dbBackupSizeByte = 0;
    private int $fileBackupSizeByte = 0;

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

        $this->deleteUpdaterDir();
        $this->deleteLogs();
        $this->backupDb();
        $this->backupNextCloudDir();
        $this->backupDataDir();

        $this->maintenanceMode(false);
        
        $this->log(sprintf('Total backup size: %s', $this->formatBytes($this->totalBackupSizeByte)));
        $this->log(sprintf('Total backup duration: %s sec.', time() - $this->backupStartTimestamp));
        $this->log('### BACKUP FINISHED ###');
    }

    private function init()
    {
        $time = new DateTime();
        $this->backupStartTimestamp = $time->getTimestamp();
        $this->backupStartTimeString = $time->format(self::DATETIME_FOMRAT);

        $this->baseFilename = sprintf('%s___%s', $this->backupStartTimeString, $this->target->name);
        $this->logFilePath = sprintf('%s/%s%s', $this->target->backupDir, $this->baseFilename, '.nextcloud-backup.log');
        $this->sqlBackupFilePath = sprintf('%s/%s%s', $this->target->backupDir, $this->baseFilename, '.nextcloud-backup.sql');
        $this->ncDirBackupFilePath = sprintf('%s/%s%s', $this->target->backupDir, $this->baseFilename, '.nextcloud-backup.zip');
        $this->dataDirBackupFilePath = $this->target->dataDir ? sprintf('%s/%s%s', $this->target->backupDir, $this->baseFilename, '.nextcloud-backup.data.zip') : '';
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
            'ncDirBackupFilePath' => $this->ncDirBackupFilePath,
            'dataDirBackupFilePath' => $this->dataDirBackupFilePath,
            'backupStartTimeString' => $this->backupStartTimeString,
            'backupStartTimestamp' => $this->backupStartTimestamp,
            'phpVersion' => PHP_VERSION,
            'phpMaxExecutionTime' => ini_get('max_execution_time'),
            'phpMemoryLimit' => ini_get('memory_limit'),
        ];

        $this->log(json_encode($data), false);
        $this->log(sprintf('### alddesign/nextcloud-backup %s ###', APP_VERSION));
        $this->log(sprintf('### STARTING BACKUP %s of %s ###', $this->no, $this->total));

        if(!is_dir($this->target->backupDir))
        {
            throw new Exception(sprintf('Path to backup directory not found: "%s"', $this->target->backupDir));
        }
        if(!is_file($this->nextCloudConfigPhpPath))
        {
            throw new Exception(sprintf('Nextclouds config.php not found: "%s"', $this->nextCloudConfigPhpPath));
        }
        if($this->target->dataDir && !is_dir($this->target->dataDir))
        {
            throw new Exception(sprintf('Path to Nextcloud data directory not found: "%s"', $this->target->dataDir));
        }

        $this->nextCloudConfig = $this->loadNextCloudConfig();

        $this->log(sprintf('Target name: "%s"', $this->target->name));
        $this->log(sprintf('Nextcloud version of target: %s', $this->cGet('version', 'unknown')));
    }

    /** 
     * Lets do this in a separate method
     * @return array The $CONFIG array from nextcloud
     */
    private function loadNextCloudConfig()
    {
        require $this->nextCloudConfigPhpPath; //Now we should have as $CONFIG
        if(!isset($CONFIG) || !is_array($CONFIG))
        {
            throw new Exception('Invalid nextcloud config.php. $CONFIG is missing or invalid.');
        }

        return $CONFIG;
    }

    private function deleteOldBackups()
    {
        $this->log('# Starting: deleting old backups');

        $keep = $this->target->backupsToKeep;
        if($keep <= 0)
        {
            $this->log('No old backups to deleted. Number of backups to keep is 0 (keep all)');
            return;
        }

        //Find all the .log file & parase the first line line, which is json data
        /** @var string[] */
        $backups = [];
        $filenames = scandir($this->target->backupDir);
        foreach($filenames as $name)
        {
            if(str_ends_with($name, sprintf('___%s.nextcloud-backup.log', $this->target->name)))
            {
                $f = fopen(sprintf('%s/%s', $this->target->backupDir, $name), 'r');
                $line = !feof($f) ? fgets($f) : '';
                fclose($f);

                $data = json_decode($line, true);
                if(is_array($data) && (($data['targetName'] ?? '') === $this->target->name))
                {
                    $backups[intval($data['backupStartTimestamp'] ?? 0)] = $data;
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
            $this->deleteBackupFile($data['logFilePath'] ?? '');
            $this->deleteBackupFile($data['sqlBackupFilePath'] ?? '');
            $this->deleteBackupFile($data['ncDirBackupFilePath'] ?? '');
            if($data['dataDirBackupFilePath'] ?? '') 
                $this->deleteBackupFile($data['dataDirBackupFilePath']);
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

    private function deleteUpdaterDir()
    {
        if(!$this->target->deleteUpdaterDir)
        {
            return;
        }

        $this->log('# Starting: deleting nextcloud-updater-backup directory');

        $instanceId = $this->cNeed('instanceid');
        $path = sprintf('%s/data/updater-%s/', $this->target->path, $instanceId);

        if(!file_exists($path))
        {
            $this->log(sprintf('No nextcloud-updater-backup directory "%s" found - nothing to delete', $path));
            return;
        }

        $command = sprintf('rm -R %s', escapeshellarg($path));
        exec($command, $output, $result);

        if($result !== 0)
        {
            $output = implode(";\n", $output);
            throw new Exception(sprintf('Error deleting nextcloud-updater-backup directory. Result code: %s. Output %s', $result, $output));
        }


        $this->log(sprintf('Deleted nextcloud-updater-backup directory "%s"', $path));
    }

    private function deleteLogs()
    {
        if(!$this->target->deleteLogs)
        {
            return;
        }

        $this->log('# Starting: deleting nextcloud logs');

        $dir = $this->target->dataDir ? $this->target->dataDir : $this->target->path . '/data';
        $files = scandir($dir);
        if($files)
        {
            foreach($files as $file)
            {
                $path = sprintf('%s/%s', $dir, $file);
                if(str_ends_with($file, '.log') && is_file($path))
                {
                    unlink($path);
                    $this->log(sprintf('Deleted "%s"', $path));
                }
            }
        }
    }

    private function backupNextCloudDir()
    {
        $this->log(sprintf('# Starting nextcloud directory backup to: %s', $this->ncDirBackupFilePath));
        $this->log(sprintf('Backing up nextclound directory: %s', $this->target->path));

        // Execute the shell command
        $command = sprintf('cd %s && zip -r %s ./', escapeshellarg($this->target->path), escapeshellarg($this->ncDirBackupFilePath));
        #$command = sprintf('touch %s', escapeshellarg($this->ncDirBackupFilePath));
        $start = microtime(true);
        exec($command, $output, $result);
        $duration = round(microtime(true) - $start, 3);

        if($result !== 0)
        {
            $output = implode("\n", $output);
            throw new Exception(sprintf('Error running "zip". Result code: %s. Output %s', $result, $output));
        }

        $size = filesize($this->ncDirBackupFilePath);
        $speed = $this->formatBytes($size / $duration);
        $this->totalBackupSizeByte += $size;
        $this->fileBackupSizeByte += $size;

        $this->log(sprintf('Created backup file with %s in %s seconds. (%s / sec.)', $this->formatBytes($size), $duration, $speed));
        $this->log('Nextcloud directory backup finished.');
    }

    private function backupDataDir()
    {
        if(!$this->dataDirBackupFilePath || !$this->target->dataDir)
        {
            return;
        }

        $this->log(sprintf('# Starting data directory backup to: %s', $this->dataDirBackupFilePath));
        $this->log(sprintf('Backing up data directory: %s', $this->target->dataDir));

        // Execute the shell command
        $command = sprintf('cd %s && zip -r %s ./', escapeshellarg($this->target->dataDir), escapeshellarg($this->dataDirBackupFilePath));
        $start = microtime(true);
        exec($command, $output, $result);
        $duration = round(microtime(true) - $start, 3);

        if($result !== 0)
        {
            $output = implode("\n", $output);
            throw new Exception(sprintf('Error running "zip". Result code: %s. Output %s', $result, $output));
        }

        $size = filesize($this->dataDirBackupFilePath);
        $speed = $this->formatBytes($size / $duration);
        $this->totalBackupSizeByte += $size;
        $this->fileBackupSizeByte += $size;

        $this->log(sprintf('Created backup file with %s in %s seconds. (%s / sec.)', $this->formatBytes($size), $duration, $speed));
        $this->log('Data directory backup finished.');
    }

    /** @see asdfds */
    private function backupDb()
    {
        $dbHost = $this->cNeed('dbhost');
        $dbName = $this->cNeed('dbname');
        $dbUser = $this->cNeed('dbuser');
        $dbPassword = $this->cNeed('dbpassword');

        $this->log(sprintf('# Starting DB backup to: %s', $this->sqlBackupFilePath));
        $this->log(sprintf('Backing up nextcloud DB: Host: "%s", Name: "%s", User: "%s"', $dbHost, $dbName, $dbUser));

        //Executing shell command
        
        //Build command
        //See: https://docs.nextcloud.com/server/22/admin_manual/configuration_database/mysql_4byte_support.html 
        $utf8mb4 = ($this->nextCloudConfig['mysql.utf8mb4'] ?? false) ? ' --default-character-set=utf8mb4' : '';         
        $command = sprintf('mysqldump --single-transaction%s -h %s -u %s -p%s %s > %s', 
            $utf8mb4,
            $dbHost, 
            $dbUser, 
            $dbPassword, 
            $dbName, 
            $this->sqlBackupFilePath
        );

        $start = microtime(true);
        exec($command, $output, $result);
        $duration = round(microtime(true) - $start, 3);
        
        if($result !== 0)
        {
            $output = implode(";\n", $output);
            throw new Exception(sprintf('mysqldump result code: %s, %s', $result, $output));
        }

        $size = filesize($this->sqlBackupFilePath);
        $speed = $this->formatBytes($size / $duration);
        $this->totalBackupSizeByte += $size;
        $this->dbBackupSizeByte += $size;

        $this->log(sprintf('Created backup file with %s in %s seconds. (%s / sec.)', $this->formatBytes($size), $duration, $speed));
        $this->log('DB backup finished');
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
            $this->log(sprintf('# Enabled maintenance mode - waiting %s seconds', $this->target->maintainWait));
            sleep($this->target->maintainWait);
        }
        else
        {
            //Just write file
            $content = file_put_contents($this->nextCloudConfigPhpPath, $content);
            $this->log('# Disabled maintenance mode');
        }
    }

    /** @return int|false */
    public function log(string $message, bool $time = true)
    {
        if($time)
            $line = sprintf('%s: %s%s', (new DateTime())->format(self::DATETIME_FOMRAT), $message, "\n");
        else
            $line = sprintf('%s%s', $message, "\n");

        return file_put_contents($this->logFilePath, $line, FILE_APPEND | LOCK_EX);
    }

    private function logWarning(string $message)
    {
        $this->log(sprintf('[WARNING]: %s', $message));
    }

    /** Loads a value from the nextcloud config array. Throws error if it not exists. */
    private function cNeed(string $key, bool $errorIfEmpty = true)
    {
        if(!isset($this->nextCloudConfig[$key]))
        {
            throw new Exception(sprintf('Faild to load "%s" from nextclouds config.php.', $key));
        }

        if($errorIfEmpty && empty($this->nextCloudConfig[$key]))
        {
            throw new Exception(sprintf('"%s" must not be empty in nextclouds config.php.', $key));
        }

        return $this->nextCloudConfig[$key];
    }

    /** Loads a value from the nextcloud config array. */
    private function cGet(string $key, $default = '')
    {
        return $this->nextCloudConfig[$key] ?? $default;
    }

    /** Format Bytes into readable untis: 1024 bytes = 1 KiB */
    private function formatBytes($bytes, int $precision = 2) 
    { 
        $units = ['B', 'KiB', 'MiB', 'GiB', 'TiB']; 
       
        $bytes = intval($bytes);
        $bytes = max($bytes, 0); 
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
        $pow = min($pow, count($units) - 1); 
       
        $bytes /= pow(1024, $pow);
       
        return round($bytes, $precision) . ' ' . $units[$pow]; 
    } 

}

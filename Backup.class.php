<?php
declare(strict_types = 1);

class Backup
{
    private string $backupTime = '';
    private string $targetName = '';
    private Target $target;
    private int $no = 0;
    private int $total = 0;

    private string $nextCloudConfigPhpPath = '';
    private string $logFilePath = '';
    private string $sqlBackupFilePath = '';
    private string $dataBackupFilePath = '';

    public function __construct(Target $target, string $targetName, int $no, int $total)
    {       
        $this->target = $target;
        $this->targetName = $targetName; 
        $this->no = $no;
        $this->total = $total;
    }

    public function run()
    {
        try
        {
            $this->init();

            $this->maintenanceMode(true);

            $this->backupDb();
            $this->backupData();

            $this->maintenanceMode(false);
            $this->log('##### BACKUP FINISHED #####');
        }
        catch(Exception $ex)
        {
            $this->error($ex->getMessage());
        }
    }

    private function init()
    {
        $this->backupTime = (new DateTime())->format('Y-m-d_H-i-s');

        $this->logFilePath = $this->getBackupFilePath('.log');
        $this->sqlBackupFilePath = $this->getBackupFilePath('.sql');
        $this->dataBackupFilePath = $this->getBackupFilePath('.zip');
        $this->nextCloudConfigPhpPath = sprintf('%s/config/config.php', $this->target->path);

        $this->log(sprintf('########## alddesign/nextcloud-backup %s ##########', APP_VERSION));
        $this->log(sprintf('PHP max_execution_time=%s', ini_get('max_execution_time')));
        $this->log(sprintf('PHP memory_limit=%s', ini_get('memory_limit')));
        $this->log(sprintf('##### STARTING BACKUP %s of %s #####', $this->no, $this->total));

        if(!file_exists(BACKUP_DIR))
        {
            $this->error('Backup directory not found - check path');
        }
        if(!file_exists($this->nextCloudConfigPhpPath))
        {
            $this->error('Nextclouds config.php not found - check path');
        }

        $this->log(sprintf('Nextcloud version of target: %s', $this->getNexcloudVersion()));
    }

    private function backupData()
    {
        $command = sprintf('cd %s && zip -r %s ./', $this->target->path, $this->dataBackupFilePath);
        
        // Execute the shell command
        $this->log('Starting data backup: ' . $this->dataBackupFilePath);
        exec($command, $output, $result);
        
        if($result !== 0)
        {
            $output = implode(";\n", $output);
            throw new Exception(sprintf('zip result code: %s, %s', $result, $output));
        }
        $this->log('Data backup finished: ' . $this->dataBackupFilePath);
    }    

    private function backupDb()
    {
        $t = $this->target;

        $this->log('Starting DB backup: ' . $this->sqlBackupFilePath);
        $command = sprintf('mysqldump --single-transaction -h %s -u %s -p%s %s > %s', $t->dbHost, $t->dbUser, $t->dbPassword, $t->dbName, $this->sqlBackupFilePath);
        exec($command, $output, $result);
        
        if($result !== 0)
        {
            $output = implode(";\n", $output);
            throw new Exception(sprintf('mysqldump result code: %s, %s', $result, $output));
        }

        $this->log('DB backup finished: ' . $this->sqlBackupFilePath);
    }

    private function getNexcloudVersion()
    {
        require $this->nextCloudConfigPhpPath; //Now we should have as $CONFIG
        
        $version = $CONFIG['version'] ?? 'unknown';
        unset($CONFIG);
        
        return $version;
    }

    /**
     * Generates absolute path the backupfile we want to create.
     * @param string $extenstion The desired filename extension with dot (.zip .sql .log)
     */
    private function getBackupFilePath(string $extenstion)
    {
        $filename = str_replace(['{backupTime}', '{targetName}'], [$this->backupTime, $this->targetName], BACKUP_FILENAME_TPL) . $extenstion;
        return sprintf('%s/%s', BACKUP_DIR, $filename);
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
            $this->log(sprintf('Enabled maintenance mode - waiting %s seconds', MAINTAIN_WAIT));
            sleep(MAINTAIN_WAIT);
        }
        else
        {
            //Just write file
            $content = file_put_contents($this->nextCloudConfigPhpPath, $content);
            $this->log('Disabled maintenance mode');
        }
    }

    public function log(string $message)
    {
        if(CREATE_LOG)
        {
            $line = sprintf('%s: %s%s', $this->now(), $message, "\n");
            file_put_contents($this->logFilePath, $line, FILE_APPEND | LOCK_EX);
        }
    }

    private function error(string $message)
    {
        $message = sprintf('ERROR: %s', $message);
        
        http_response_code(500);
        echo $message;
        
        $this->log($message);

        die;
    }

    private function now()
    {
        return (new DateTime())->format('Y-m-d_H-i-s.u');
    }
}

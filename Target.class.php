<?php
declare(strict_types = 1);

class Target
{
    /** @var string Absolute path to directory that shall be backed up - without trailing slash */
    public string $name;
    public string $path; 
    public string $dbHost;
    public string $dbName;
    public string $dbUser;
    public string $dbPassword;
    public int $backupsToKeep = 0;
    public int $maintainWait = 0;
    public string $backupDir = '';

    /**
     * @param string $name Unique identifier of the nextcloud target
     * @param string $data Raw target from config.php TARGET constant
     */
    public function __construct(string $name, array $data)
    {
        $this->name = $name;
        $this->path = self::removeTrailingSlashes((string)$data['path'] ?? '');
        $this->dbHost = (string)$data['dbHost'] ?? '';
        $this->dbName = (string)$data['dbName'] ?? '';
        $this->dbUser = (string)$data['dbUser'] ?? '';
        $this->dbPassword = (string)$data['dbPassword'] ?? '';

        $this->backupsToKeep = (int)$data['backupsToKeep'] ?? 0;
        $this->backupsToKeep = $this->backupsToKeep > 0 ? $this->backupsToKeep : 0;

        $this->maintainWait = (int)$data['maintainWait'] ?? 0;
        $this->maintainWait = $this->maintainWait > 0 ? $this->maintainWait : MAINTAIN_WAIT;

        $this->backupDir = (string)$data['backupDir'] ?? '';
        $this->backupDir = $this->backupDir !== '' ? $this->backupDir : BACKUP_DIR;
        $this->backupDir = self::removeTrailingSlashes($this->backupDir);
    }

    private static function removeTrailingSlashes(string $val)
    {
        return rtrim($val, '/\\');
    }

}
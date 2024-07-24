<?php
declare(strict_types = 1);

class Target
{
    /** @var string Name of the target as defined in config.php (array key) */
    public string $name;
    /** @var string Path to the nextcloud dir */
    public string $path; 
    /** @var int Number of backups to keep */
    public int $backupsToKeep = 0;
    /** @var int Wait time in seconds after enabling/disabling maintainance mode */
    public int $maintainWait = 0;
    /** @var string The director where the backup is saved to */
    public string $backupDir = '';
    /** @var bool Delete the /data/updater-(instance-id)/ directory */
    public bool $deleteUpdaterDir = false;

    /**
     * @param string $name Unique identifier of the nextcloud target
     * @param string $data Raw target from config.php TARGET constant
     */
    public function __construct(string $name, array $data)
    {
        $this->name = $name;
        $this->path = self::removeTrailingSlashes((string)$data['path'] ?? '');

        $this->backupsToKeep = (int)($data['backupsToKeep'] ?? 0);
        $this->backupsToKeep = $this->backupsToKeep > 0 ? $this->backupsToKeep : 0;

        $this->maintainWait = (int)($data['maintainWait'] ?? 0);
        $this->maintainWait = $this->maintainWait > 0 ? $this->maintainWait : MAINTAIN_WAIT;

        $this->backupDir = (string)($data['backupDir'] ?? '');
        $this->backupDir = $this->backupDir !== '' ? $this->backupDir : BACKUP_DIR;
        $this->backupDir = self::removeTrailingSlashes($this->backupDir);

        $this->deleteUpdaterDir = ($data['deleteUpdaterDir'] ?? false) === true;
    }

    private static function removeTrailingSlashes(string $val)
    {
        return rtrim($val, '/\\');
    }

}
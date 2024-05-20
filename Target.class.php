<?php
declare(strict_types = 1);

class Target
{
    /** @var string Absolute path to directory that shall be backed up - without trailing slash */
    public string $path; 
    public string $dbHost;
    public string $dbName;
    public string $dbUser;
    public string $dbPassword;
    public int $numberOfBackupsToKeep = 0;
    public int $maintainWait = 0;

    /**
     * @param string $id Unique identifier of the nextcloud target
     * @param string $path Absolute path to directory that shall be backed up
     */
    public function __construct(string $path, string $dbHost, string $dbName, string $dbUser, string $dbPassword, int $numberOfBackupsToKeep = 0, int $maintainWait = 0)
    {
        $this->path = Backup::removeTrailingSlash($path);
        $this->dbHost = $dbHost;
        $this->dbName = $dbName;
        $this->dbUser = $dbUser;
        $this->dbPassword = $dbPassword;
        $this->numberOfBackupsToKeep = $numberOfBackupsToKeep > 0 ? $numberOfBackupsToKeep : 0;
        $this->maintainWait = $maintainWait <= 0 ? MAINTAIN_WAIT : $maintainWait;
    }


}
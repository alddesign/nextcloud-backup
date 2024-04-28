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

    /**
     * @param string $path Absolute path to directory that shall be backed up - without trailing slash
     */
    public function __construct(string $path, string $dbHost, string $dbName, string $dbUser, string $dbPassword)
    {
        $this->path = $path;
        $this->dbHost = $dbHost;
        $this->dbName = $dbName;
        $this->dbUser = $dbUser;
        $this->dbPassword = $dbPassword;
    }
}
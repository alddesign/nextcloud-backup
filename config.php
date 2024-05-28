<?php
define('APP_VERSION', '1.7.0');
define('KEY', ''); //Set to random string!
define('BACKUP_DIR', '/var/www/backups');
define('MAINTAIN_WAIT', 90);
define('TARGETS', 
[
    //List all the nextcloud instance you want to backup in this array:
    'my-nextcloud' => 
    [
        'path' => '/var/www/htdocs/nextcloud',  //Absolute path to the nextcloud directory (where the index.php is located)
        'dbHost' => 'localhost',                //The hostname of the nextcloud mysql database
        'dbName' => 'NextcloudDB',              //The name of the nextcloud mysql database
        'dbUser' => 'user',                     //The username to access the nextcloud database
        'dbPassword' => 'password',             //The password for this user
        'backupsToKeep' => 5,                   //Optional: Specify how many backups do you want to keep for this target. Ommit or 0 to keep all. (Olddest backups will be deleted first).
        'maintainWait' => 60,                   //Optional: Override MAINTAIN_WAIT for this target. Ommit or 0 for default value
        'backupDir' => '/var/www/backups-2'      //Optional: Override BACKUP_DIR for this target. Ommit or empty '' for default value
    ],
    //'my-other-nextcloud' = [...],
    //'my-third-nextcloud' = [...],
]);

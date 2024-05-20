<?php
define('APP_VERSION', '1.6.0');
define('KEY', ''); //Set to random string
define('BACKUP_DIR', '/absolute/path/to/backup/directory');
define('MAINTAIN_WAIT', 90);
define('TARGETS', 
[
    //List all the nextcloud instance you want to backup in this array:

    'my-nextcloud' => new Target //Array key: each target needs a unique name (should contain only url & path safe characters)
    (
        '/absolute/path/to/nextcloud/directory', //Absolute path to the nextcloud directory (where the index.php is located)
        'nextcloud-db-host', //The hostname of the nextcloud mysql database
        'db-name', //The name of the nextcloud mysql database
        'db-user', //The username to access the nextcloud database
        'db-password', //The password for this user
        5, //Optional: Specify how many backups do you want to keep for this target. Ommit or 0 to keep all. (Olddest backups will be deleted first).
        60 //Optional: Override MAINTAIN_WAIT for this target. Ommit or 0 for default value
    ),

    //next target,...
]);

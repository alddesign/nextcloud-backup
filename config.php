<?php
define('APP_VERSION', '1.3.0');
define('KEY', ''); //Set to random string
define('TARGETS', 
[
    'my-nextcloud' => new Target('/absolute/path/to/nextcloud/directory', 'nextcloud-db-host', 'db-name', 'db-user', 'db-password')
]);
define('BACKUP_DIR', '/absolute/path/to/backup/directory');
define('BACKUP_FILENAME_TPL', '{backupTime}___{targetName}');
define('CREATE_LOG', true);
define('MAINTAIN_WAIT', 90);
<?php
/**
 * Configration file for nextcloud-backpu.
 * You HAVE TO modify this file for your needs.
 */

/** Set to random, urls safe string. This key  */
define('KEY', '');

/** Absolute path to the directory where the backups will be stored */
define('BACKUP_DIR', '/var/www/backups/');

/** Wait timeout, see section MAINTAIN_WAIT in README.md for details */
define('MAINTAIN_WAIT', 90);

/** List all the nextcloud instances you want to backup in this array: */
define('TARGETS', 
[
    /** Unique Identifier of the Target */
    'my-nextcloud' => 
    [
        /** 
         * Absolute path to the nextcloud directory (where the index.php is located) 
         */
        'path' => '/var/www/htdocs/nextcloud/',
        
        /** 
         * OPTIONAL: Specify how many backups do you want to keep for this target. Omit or 0 to keep all. 
         * Olddest backups will be deleted first. 
         */
        'backupsToKeep' => 5,                   
        
        /**
         * OPTIONAL: Override MAINTAIN_WAIT for this target. 
         */
        'maintainWait' => 60,
        
        /**
         * OPTIONAL: Override BACKUP_DIR for this target.
         */
        'backupDir' => '/var/www/my_backups/',
        
        /**
         * OPTIONAL: Delete the data/updater-<instance-id>/ before backing up. 
         * On every backup, nextcloud itself creates a backup in this directory.
         * This can fill up your disk space very quickly, so it is recommended to set this option to true.
         */
        'deleteUpdaterDir' => true,
        
        /**
         * OPTIONAL: If your nextcloud data directory is not located inside the nextcloud directory (which is the default location). 
         * This will create a separate .zip file for the data direcory.
         */
        'dataDir' => '/var/www/nextcloud_data/',
    ],
    //'my-second-nextcloud' => [...],
    //'my-third-nextcloud' => [...],
]);

/** Ignore */
define('APP_VERSION', '1.9.0');
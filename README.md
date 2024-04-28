# nextcloud-backup
Simple PHP script to backup nextclound instances running on a shared webspace. 

[Requirements](#Requirements)  
[Configuration](#Configuration)  
[Usage](#Usage)  
[Notes](#Notes)  

## Requirements
- PHP needss read/write access to the nextcloud and backup directory
- PHP has to be able to run shell commands: *zip* and *mysqldump*

## Configuration
See **config.php**
- `KEY` You **have to** set this to a random string (should be url safe)
- `TARGETS` Array of type Target: Nextcloud instances to backup. Array key is the unique target name (should be url safe). There is an example in config.php
- `BACKUP_DIR` Absolute path to the directory where the backups shall be stored
- `BACKUP_FILENAME_TPL` Template for the name of the backup files. Placeholders are *{backupTime}* and *{targetName}*. Do not add any file extentsion
- `CREATE_LOG` If true, a .log file will be created alongside the backup files
- `MAINTAIN_WAIT` Number of seconds to wait after the maintenance mode was activated. See [Notes](#Notes) for more information about that value.

## Usage
To run a backup, make a HTTP GET request to the **index.php** with the following parameters:
- `key` The KEY defined in config.php
- `target` The name of the target to backup, like definded in config.php
- `all=1` Backup all targets defined in config.php

Examples:  
`https://myhost.com/nextcloud-backup/index.php?key=123456&target=nextcloud01`
`https://myhost.com/nextcloud-backup/index.php?key=123456&all=1`  

## Notes
- If everything worked correctly, you get a **HTTP 200** response and the response text is **"1"**. 

- When there is an error, you get a **HTTP 500** response and the respose text contains the error message.
Addidtional infos can be found in the **.log** file in the backup directory (if the error was not fatal).

- A backup consists of these files:
  - **.sql** the database
  - **.zip** the entire nextcloud directory
  - **.log** Log file (if enabled) 

- `MAINTAIN_WAIT` explained:  
You need to account for PHP *opcache.revalidate_freq* value in your nextcloud environment, because the maintainance mode is enabled/disabled by editing nextclouds *config.php*.  
You might also want to wait a little bit longer so that all nextcloud operations and background jobs have finished before the backup starts.  
***opcache.revalidate_freq* + 30 seconds is a good value.**
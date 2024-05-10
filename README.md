# nextcloud-backup
Simple PHP script to backup nextclound instances running on a shared webspace.  
Because [How hard can it be](#how-hard-can-it-be) 

[Requirements](#requirements)  
[Installation](#installation)  
[Configuration](#configuration)  
[Usage](#usage)  
[Restore](#restore)  
[Notes](#notes)  

## Requirements
- PHP 8.x
- PHP needs read/write access to the nextcloud and backup directory
- PHP has to be able to run shell commands: *zip* and *mysqldump* (in a future release there might be an option for using pure PHP)

## Installation
Upload this repository to a place on you webspace which is accessible over the internet. If possible: not into your nextcloud directory. 

## Configuration
See **config.php**
- `KEY` You **have to** set this to a random string (should be url safe)
- `TARGETS` Array of type Target: Nextcloud instances to backup. Array key is the unique target name (should be url safe). There is an example in config.php
- `BACKUP_DIR` Absolute path to the directory where the backups shall be stored
- `BACKUP_FILENAME_TPL` Template for the name of the backup files. Placeholders are *{backupTime}* and *{targetName}*. Do not add any file extentsions
- `CREATE_LOG` If true, a .log file will be created alongside the other backup files. It contains infos about the backup process, PHP and nextcloud.
- `MAINTAIN_WAIT` Number of seconds to wait after the maintenance mode was activated. See [MAINTAIN_WAIT](#maintain_wait) for more information about that value.

## Usage
To run a backup, make a HTTP GET request to the **index.php**. You can do this manually or via WebCRON (which most hosting providers offer). Supply the following parameters:
- `key` The KEY defined in config.php
- `target` The name of the target to backup, like definded in config.php
- `all=1` Backup all targets defined in config.php

Examples:  
`https://myhost.com/nextcloud-backup/index.php?key=123456&target=nextcloud01`
`https://myhost.com/nextcloud-backup/index.php?key=123456&all=1`  

## Restore
Very straight forward - for more infos see [Backup files](#backup-files)
- Restore the **Nextcloud Database** using the **.sql** file.  
You can import it using [phpMyAdmin](https://www.phpmyadmin.net/) which most hosting providers offer out of the box.
- Restore the completet **Nextcloud Directory** by extracting the **.zip** file.  
There are plenty of ways to do this: use the file management tool your hosting provider provides, Use [Tiny File Manager](https://tinyfilemanager.github.io/) - A PHP filemanager consisting of only one php file,...
- Disable maintenance mode by setting `'maintenance' => false` in nextclouds `config/config.php`
- Done, open your nextcloud

## Notes
### Script Responses
- If everything worked correctly, the response text is **"nextcloud-backup-successful" (HTTP 200)**.
- When there is an error, the response text contains the **error message (HTTP 500)** if possible.
Addidtional infos can be found in the **.log** file in the backup directory (if the error was not fatal).

### Backup files
A backup consists of these files:
  - **.sql** the nextcloud database as a full dump.
  - **.zip** the entire nextcloud directory.
  - **.log** Log file (if enabled). It contains infos about the backup process, PHP and nextcloud.

### MAINTAIN_WAIT
Imporant notes about `MAINTAIN_WAIT` in `config/config.php`:  
You need to account for PHP *opcache.revalidate_freq* value in your nextcloud environment, because the maintainance mode is enabled/disabled by editing nextclouds *config.php*.  
You might also want to wait a little bit longer so that all nextcloud operations and background jobs have finished before the backup starts.  
***opcache.revalidate_freq* + 30 seconds is a good value.**

For more information: https://docs.nextcloud.com/server/28/admin_manual/installation/server_tuning.html#enable-php-opcache 

### How hard can it be
(Rant) Simple solutions which "just work" do not exist, or are not free...

The nextcloud backup app: While offering good options for where and how to store backups, simply does not work that well (not at all on most shared web spaces). Restore, while flexible, is complicated and requires ooc and shell access. The whole thing is far from intuitive, and when you want a backup NOW - good luck, maybe in a few hours or so. Oh and the documentation is always "still in writing".

To be fair, my solution has its weaknesses too. Only full backups, no built-in backup schedule, no encryption or upload to external locations like ftp, google drive or whatever.  
On the other hand: the backup method is dead-simple and reliable, restore is easy and doable on any webspace. It can be configured to backup multiple nextcloud instances. Independent from nextcloud: Nextcloud updates will not break this script, and this script will not break nextcloud. I think its a good solution for smaller instances that need just this extra bit of safety.

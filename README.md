# nextcloud-backup
Simple PHP script to backup nextclound instances running on a shared webspace.  
Because [How hard can it be](#how-hard-can-it-be) 

[Requirements](#requirements)  
[Installation](#installation)  
[Configuration](#configuration)  
[Run Backups](#run-backups)  
[Perform Restore](#perform-restore)  
[Notes](#notes)  

## Requirements
- PHP 8.x
- PHP needs read/write access to the nextcloud and backup directory
- PHP has to be able to run shell commands: *zip* and *mysqldump* (in a future release there might be an option to with pure PHP)
- Nextclound using a *mysql* database

## Installation
Upload this repository to a place on you webspace which is accessible over the internet. If possible: not into your nextcloud directory. 

## Configuration
See **config.php**
- `KEY` Set this to a random string (url safe)
- `BACKUP_DIR` Absolute path to the directory where the backups shall be stored
- `MAINTAIN_WAIT` Number of seconds to wait after the maintenance mode was activated. See [MAINTAIN_WAIT](#maintain_wait) for more information about that value.
- `TARGETS` Array of nextcloud instances to backup. There is an example in `config.php.`


## Run Backups
To run a backup, make a HTTP GET request to the `index.php`. You can do this manually or via WebCRON (which most hosting providers offer). Supply the following parameters:
- `key` The KEY defined in `config.php`
- `target` The name of the target to backup, like definded in `config.php`
- `all=1` Backup all targets defined in `config.php`

Examples:  
`https://myhost.com/nextcloud-backup/index.php?key=123456&target=my-nextcloud`
`https://myhost.com/nextcloud-backup/index.php?key=123456&all=1`  

## Perform Restore
Very straight forward - for more infos see [Backup files](#backup-files)
- Restore the *Nextcloud Database* using the `.sql` file.  
You can import it using [phpMyAdmin](https://www.phpmyadmin.net/) which most hosting providers offer out of the box.
- Restore the *Nextcloud Directory* by extracting the `.zip` file.  
There are plenty of ways to do this: use the file management tool your hosting provider provides, use [Tiny File Manager](https://tinyfilemanager.github.io/),...
- Disable maintenance mode by setting `'maintenance' => false` in nextclouds `config/config.php`
- Done, open nextcloud

## Notes
### Script Responses
- If everything worked correctly, the response text is **"nextcloud-backup-successful" (HTTP 200)**.
- When there is an error, the response text contains the **error message (HTTP 500)** if possible.
Addidtional infos can be found in the `.log` file in the backup directory (if the error was not fatal).

### Backup files
A backup consists of these files:
  - `.sql` the nextcloud database as a full dump.
  - `.zip` the entire nextcloud directory.
  - `.log` contains infos about the backup process, PHP and nextcloud. Do not delete or modify this file. Nextcloud-backup needs data stored in this file to handle automatic deletion of old backups.

### MAINTAIN_WAIT
Imporant notes about `MAINTAIN_WAIT` in `config/config.php`:  
You need to account for PHP *opcache.revalidate_freq* value in your nextcloud environment, because the maintainance mode is enabled/disabled by editing nextclouds *config.php*.  
You might also want to wait a little bit longer so that all nextcloud operations and background jobs have finished before the backup starts.  
***opcache.revalidate_freq* + 30 seconds is a good value.**

For more information: https://docs.nextcloud.com/server/28/admin_manual/installation/server_tuning.html#enable-php-opcache 

### How hard can it be
(Rant) Simple solutions which "just work" do not exist, or are not free...

The nextcloud backup app: While offering good options for where and how to store backups, simply does not work that well (not at all on most shared web spaces). Restore, while flexible, is complicated and requires *ooc*, direct shell access and exporting a key (good luck if you forgot about that and your nextcloud is dead). The whole thing is far from intuitive, and when you want to backup NOW - good luck, maybe in a few hours or so. Oh and the documentation is always "still in writing".

To be fair, my solution has its weaknesses too. Only full backups, no built-in schedule, no encryption or upload to external locations like ftp, g-drive or whatever.  
On the other hand: the backup method is dead-simple and reliable. Configuaration is done in minutes. Restore is easy and doable on any webspace. It can be configured to backup multiple nextcloud instances and also has some sort of quota management. Its independent from nextcloud: Nextcloud updates will not break this script, and this script will not break nextcloud. I think its a good solution for smaller instances and when you "just want a backup".
# Plex Local Cache

This is a project to help Plex servers which use remotely mounted storage. It attempts to manage a small local cache of videos which are likely to be watched next. 

#### Use this at your own risk.

A few basic safeguards have been put in place to try to prevent data loss, but I cannot guarantee it. Please ensure the local-cache directory is empty and unused.

## Installation

Clone the directory and run `composer install` to install the project dependencies. 

## Usage 

This project assumes that there is a Plex library whose data is stored remotely and mounted using [rclone](https://rclone.org) or similar. [Union-fs](http://manpages.ubuntu.com/manpages/trusty/man8/unionfs-fuse.8.html) can then be used to mount multiple local and remote directories to a single merged directory. 

Example:
```bash
LOCALMPOINT="/media/local"
CACHEPOINT="/media/local-cache"
REMOTEMPOINT="/media/plex"
UNIONMPOINT="/media/union"

unionfs-fuse -o cow $LOCALMPOINT=RW:$CACHEPOINT=RO:$REMOTEMPOINT=RO $UNIONMPOINT -o allow_other
```

Above, three directories are mounted to `/media/union`, the first one is read/write, the latter two are read-only. If multiple versions of the same file are found between the source directories, priority is given left to right.

Using this, we can write likely-accessed media to a higher-priority locally-cached directory to reduce buffering. 

Once there is setup similar to above, this project can be initialised by running setup.php from the project route directory. 

```bash
php setup.php
```

This will initialise the config. 

Once the configuration has been created, test the app by running

```bash
php app.php
```

It runs in dry-run mode by default so no changes will be made. If you're happy with the changes, pass in -f as an option and the app will begin the process.

## Requirements

PHP, PHP Curl, PHP XML

## Limitations

Currently, this project assumes video files are in a single file. It also works exclusively for TV Series, and only on deck. It doesn't look-ahead on deck, so won't know to cache the next on-deck item. 

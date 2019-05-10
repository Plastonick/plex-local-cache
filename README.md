# Plex Local Cache

This is a project to help Plex servers which use remotely mounted storage. It attempts to manage a small local cache of videos which are likely to be watched next. 

## Installation

Clone the directory and run `composer install` to install the project dependencies. 

## Usage 

This project assumes that there is a Plex library whose data is stored remotely and mounted using [rclone](https://rclone.org) or similar. [Union-fs](http://manpages.ubuntu.com/manpages/trusty/man8/unionfs-fuse.8.html) can then be used to mount multiple local and remote directories to a single merged directory. 

Example:
```bash
REMOTEMPOINT="/media/plex"
CACHEPOINT="/media/local-cache"
LOCALMPOINT="/media/local"
UNIONMPOINT="/media/union"

unionfs-fuse -o cow $LOCALMPOINT=RW:$CACHEPOINT=RO:$REMOTEMPOINT=RO $UNIONMPOINT -o allow_other
```

Above, three directories are mounted to `/media/union`, the first one is read/write, the latter two are read-only. If multiple versions of the same file are found between the source directories, priority is given left to right.

Using this, we can write likely-accessed media to a higher-priority locally-cached directory to reduce buffering. 

Once there is setup similar to above, this project can be initialised by running setup.php from the project route directory. 

```bash
php setup.php
```

This will initialise the config 

## Limitations

Currently, this project assumes video files are in a single file. It also works exclusively for TV Series, and only on deck. 

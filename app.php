<?php

use PlexLocalCache\App;

require_once 'vendor/autoload.php';

$options = getopt('f');

$force = false;
if (isset($options['f'])) {
    $force = true;
} else {
    echo "Running in dry-run mode, pass in -f to force\n";
}

$config = json_decode(file_get_contents(__DIR__ . '/config.json'), true);
$config['dryRun'] = !$force;

try {
    (new App($config))->run();
} catch (Exception $e) {
    echo "Error: {$e->getMessage()}\n";
}


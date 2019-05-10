<?php

$configDefaults = [
    'tvRootDir' => ['Television root directory? (Remotely mounted)', null],
    'cacheRootDir' => ['Local cache root directory? (This directory should be empty! The program will iteratively destroy files within!)', null],
    'onDeckLimit' => ['How many on-deck items should be cached at most?', 5],
    'gbLimit' => ['How much cache can be used for on-deck video storage?', 10],
    'plexUrl' => ['Plex URL?', null],
    'port' => ['Plex port?', 32400],
    'useSsl' => ['Use SSL?', true],
    'plexToken' => ['Plex API Token?', null],
];

$config = [];

echo "Setting up plex-local-cache.\n";

foreach ($configDefaults as $key => list($description, $defaultValue)) {
    if ($defaultValue !== null) {
        $description .= " [{$defaultValue}]";
    }

    $value = trim(readline("{$description} : "));
    $config[$key] = $value ?: $defaultValue;
}

file_put_contents(__DIR__ . '/config.json', json_encode($config, JSON_PRETTY_PRINT));

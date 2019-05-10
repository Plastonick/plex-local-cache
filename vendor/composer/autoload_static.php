<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit9fee3df84c6a418083aedd4380ecb282
{
    public static $prefixLengthsPsr4 = array (
        'P' => 
        array (
            'Psr\\Log\\' => 8,
            'PlexLocalCache\\' => 15,
        ),
        'M' => 
        array (
            'Monolog\\' => 8,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Psr\\Log\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/log/Psr/Log',
        ),
        'PlexLocalCache\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
        'Monolog\\' => 
        array (
            0 => __DIR__ . '/..' . '/monolog/monolog/src/Monolog',
        ),
    );

    public static $prefixesPsr0 = array (
        'j' => 
        array (
            'jc21' => 
            array (
                0 => __DIR__ . '/..' . '/jc21/plex-api/src',
            ),
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit9fee3df84c6a418083aedd4380ecb282::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit9fee3df84c6a418083aedd4380ecb282::$prefixDirsPsr4;
            $loader->prefixesPsr0 = ComposerStaticInit9fee3df84c6a418083aedd4380ecb282::$prefixesPsr0;

        }, null, ClassLoader::class);
    }
}

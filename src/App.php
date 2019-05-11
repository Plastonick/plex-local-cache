<?php

namespace PlexLocalCache;

use jc21\PlexApi;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerAwareTrait;
use RuntimeException;

class App
{
    use LoggerAwareTrait;

    const GIGA = 10e8;

    private $config;

    private $isDryRun;

    public function __construct(array $config)
    {
        $this->validateConfig($config);
        $this->config = $config;
        $this->isDryRun = $config['dryRun'];
        $this->logger = $this->buildLogger();
    }

    public function run()
    {
        list($toCache, $expiredCache) = $this->determineCacheStatus();

        // Remove expired cache, the video has either been watched or it is no longer low enough on-deck
        $this->removeItems($expiredCache);

        // Cache the videos which aren't in the cache
        /** @var Video $videoCache */
        foreach ($toCache as $videoCache) {
            $destination = $this->getConfig('cacheRootDir') . $videoCache->getLocation();
            $source = $this->getConfig('plexRootDir') . $videoCache->getLocation();
            $destinationDirectory = dirname($destination);

            if (!file_exists($destinationDirectory)) {
                $this->logger->info('Creating directory since it does not exist.', ['dir' => $destinationDirectory]);

                if (!$this->isDryRun) {
                    mkdir($destinationDirectory, 0777, true);
                }
            }

            $this->moveFile($source, $destination);
        }
    }

    private function getConfig($option)
    {
        return isset($this->config[$option]) ? $this->config[$option] : null;
    }

    private function iterateDirectory($dir, array $items)
    {
        if (!file_exists($dir)) {
            $this->logger->warning('Attempted to iterate through directory but it does not exist!', ['dir' => $dir]);

            return $items;
        }

        $children = array_diff(scandir($dir), ['.', '..']);
        foreach ($children as $cacheItem) {
            $path = $dir . DIRECTORY_SEPARATOR . $cacheItem;

            if (is_file($path)) {
                $items[] = $path;
            } else {
                $items = $this->iterateDirectory($path, $items);
            }
        }

        return $items;
    }

    private function getVideoLibrarySection(array $librarySections, array $video)
    {
        $librarySectionUuid = $video['librarySectionUUID'];

        $availableSections = array_filter(
            $librarySections,
            function (array $section) use ($librarySectionUuid) {
                return $section['uuid'] === $librarySectionUuid;
            }
        );

        return reset($availableSections);
    }

    private function getAvailableBytes($maxUsageGigabytes)
    {
        return self::GIGA * $maxUsageGigabytes;
    }

    private function moveFile($source, $destination)
    {
        $this->logger->info('Copying file to local cache.', ['src' => $source, 'dest' => $destination]);

        if ($this->isDryRun) {
            return;
        }

        $tmpFile = $destination . '.tmp';

        if (copy($source, $tmpFile) === false) {
            $this->logger->error('Failed to copy file.', ['src' => $source, 'dest' => $tmpFile]);

            return;
        }

        if (rename($tmpFile, $destination) === false) {
            $this->logger->error('Failed to move temporary file.', ['src' => $tmpFile, 'dest' => $destination]);
        }

        $this->logger->info('Successfully moved file to local cache.', ['src' => $source, 'dest' => $destination]);
    }

    private function buildLogger()
    {
        $logDirectory = $this->getConfig('logDir') ?: __DIR__ . '/../plex-local-cache.log';

        $loggerName = $this->isDryRun ? 'plex-local-cache-dry-run' : 'plex-local-cache';
        $logger = new Logger($loggerName);
        $logger->pushHandler(new StreamHandler($logDirectory));
        $logger->pushHandler(new StreamHandler('php://stdout'));

        return $logger;
    }

    private function determineCacheStatus()
    {
        $client = new PlexApi($this->getConfig('plexUrl'), $this->getConfig('port'), $this->getConfig('useSsl'));
        $client->setToken($this->getConfig('plexToken'));

        $onDeckVideos = array_slice($client->getOnDeck()['Video'], 0, $this->getConfig('onDeckLimit'));

        $toCache = [];
        $cachedItems = $this->iterateDirectory($this->getConfig('cacheRootDir'), []);
        $bytesAvailable = $this->getAvailableBytes($this->getConfig('gbLimit'));

        foreach ($onDeckVideos as $onDeckVideo) {
            $plexLocation = $onDeckVideo['Media']['Part']['file'];

            $relativeLocation = str_replace($this->getConfig('containerRootDir'), '', $plexLocation);
            $actualLocation = str_replace($this->getConfig('containerRootDir'), $this->getConfig('plexRootDir'), $plexLocation);

            if (!file_exists($actualLocation)) {
                $this->logger->critical('Unable to find video file.', ['location' => $actualLocation]);

//                throw new RuntimeException('Unable to find video file.');
            }

            $sizeOfVideo = filesize($actualLocation);
            $cacheLocation = str_replace($this->getConfig('containerRootDir'), $this->getConfig('cacheRootDir'), $plexLocation);

            // If this video is already cached, continue and remove it from the array
            $itemKey = array_search($cacheLocation, $cachedItems);
            if ($itemKey !== false) {
                $this->logger->info('Found video in cache, continuing', ['relative_dir' => $relativeLocation]);
                unset($cachedItems[$itemKey]);
                continue;
            }

            // If there is space to cache this video, add it
            if ($bytesAvailable > $sizeOfVideo) {
                $this->logger->info(
                    'Adding video to cache',
                    ['relative_dir' => $relativeLocation, 'size' => sprintf('%.02fGB', $sizeOfVideo / self::GIGA)]
                );

                $toCache[] = new Video($onDeckVideo, $relativeLocation);
                $bytesAvailable -= $sizeOfVideo;
            }
        }

        return [$toCache, $cachedItems];
    }

    private function removeItems(array $items)
    {
        if (count($items) > $this->getMaxExpectedItems()) {
            if (!$this->getConfig('unsafeMode') && !$this->isDryRun) {
                $this->logger->error('Refusing to remove items, enable unsafeMode to bypass. Exiting.');

                throw new RuntimeException('Refusing to remove items.');
            }

            $this->logger->warning('Deleting more items than expected.', ['count' => count($items)]);
        }

        foreach ($items as $item) {
            $this->logger->notice('Deleting item.', ['item' => $item]);

            if ($this->isDryRun) {
                continue;
            }

            if (!unlink($item) ) {
                $this->logger->warning('Failed to delete item.', ['item' => $item]);
            }
        }
    }

    private function validateConfig(array $config)
    {
        $keys = [
            'dryRun',
            'plexRootDir',
            'cacheRootDir',
            'onDeckLimit',
            'gbLimit',
            'plexUrl',
            'port',
            'useSsl',
            'plexToken',
        ];

        foreach ($keys as $key) {
            if (!isset($config[$key])) {
                throw new RuntimeException("Failed to retrieve config for {$key}");
            }
        }
    }

    private function getMaxExpectedItems()
    {
        return $this->getConfig('onDeckLimit');
    }
}

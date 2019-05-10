<?php

namespace PlexLocalCache;

class Video
{
    private $meta;

    private $location;

    public function __construct(array $meta, string $location)
    {
        $this->meta = $meta;
        $this->location = $location;
    }

    public function getLocation(): string
    {
        return $this->location;
    }
}

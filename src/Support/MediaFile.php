<?php

namespace Weap\Junction\Support;

use Symfony\Component\HttpFoundation\File\File;

class MediaFile extends File
{
    /**
     * @var int
     */
    public int $mediaId;

    /**
     * @param string $path
     * @param int $mediaId
     */
    public function __construct(string $path, int $mediaId)
    {
        $this->mediaId = $mediaId;

        parent::__construct($path);
    }
}

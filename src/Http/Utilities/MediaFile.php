<?php

namespace Weap\Junction\Http\Utilities;

use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
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
        if (! is_file($path)) {
            throw new FileNotFoundException($path);
        }

        $this->mediaId = $mediaId;

        parent::__construct($path);
    }
}

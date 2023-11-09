<?php

namespace Weap\Junction\Http\Controllers\Traits;

trait HasDefaultAppends
{
    /**
     * Add this trait to your model if you want to include all accessors defined in `$appends` in the response.
     *
     * @return array
     */
    public static function defaultAppends(): array
    {
        return (new static)->appends;
    }
}

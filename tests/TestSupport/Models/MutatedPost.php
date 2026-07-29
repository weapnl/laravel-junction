<?php

namespace Weap\Junction\Tests\TestSupport\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * A post whose columns are read through a mutator, in both of Laravel's notations.
 * Such a column is still an attribute, so it stays governed by `pluck` rather than
 * by `appends` — unlike an accessor, which has no column behind it.
 */
class MutatedPost extends Post
{
    protected $table = 'posts';

    /**
     * Using old attribute notation to test compatability.
     *
     * @param string|null $value
     * @return string
     */
    public function getTitleAttribute($value): string
    {
        return strtoupper((string) $value);
    }

    /**
     * @return Attribute<string, never>
     */
    protected function body(): Attribute
    {
        return Attribute::get(fn ($value): string => strtoupper((string) $value));
    }
}

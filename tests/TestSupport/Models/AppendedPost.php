<?php

namespace Weap\Junction\Tests\TestSupport\Models;

/**
 * A post that declares `$appends` without using the HasDefaultAppends trait, to
 * pin the difference between BaseResource (which only honours `$appends` through
 * that trait) and JunctionResource (which always honours it, as plain Laravel does).
 */
class AppendedPost extends Post
{
    protected $table = 'posts';

    /**
     * @var array<int, string>
     */
    protected $appends = ['excerpt'];
}

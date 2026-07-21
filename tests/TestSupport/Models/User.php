<?php

namespace Weap\Junction\Tests\TestSupport\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $fillable = ['email'];
}

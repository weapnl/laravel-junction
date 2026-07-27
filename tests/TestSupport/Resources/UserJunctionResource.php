<?php

namespace Weap\Junction\Tests\TestSupport\Resources;

use Illuminate\Http\Request;
use Weap\Junction\Http\Resources\JunctionResource;

class UserJunctionResource extends JunctionResource
{
    public function toArray(Request $request)
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
        ];
    }
}

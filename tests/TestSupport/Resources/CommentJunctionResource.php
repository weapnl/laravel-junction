<?php

namespace Weap\Junction\Tests\TestSupport\Resources;

use Illuminate\Http\Request;
use Weap\Junction\Http\Resources\JunctionResource;

class CommentJunctionResource extends JunctionResource
{
    public function toArray(Request $request)
    {
        return [
            'post_id' => $this->post_id,
            'user_id' => $this->user_id,
            'body' => $this->body,

            'user' => new UserJunctionResource($this->whenLoaded('user')),
        ];
    }
}

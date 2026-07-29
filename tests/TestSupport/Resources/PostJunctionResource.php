<?php

namespace Weap\Junction\Tests\TestSupport\Resources;

use Illuminate\Http\Request;
use Weap\Junction\Http\Resources\JunctionResource;

class PostJunctionResource extends JunctionResource
{
    public function toArray(Request $request)
    {
        return [
            'user_id' => $this->user_id,
            'title' => $this->title,
            'body' => $this->body,
            'published_at' => $this->published_at,

            'excerpt' => $this->whenAppended('excerpt'),
            'author_name' => $this->whenAppended('author_name'),

            'comments' => CommentJunctionResource::collection($this->whenLoaded('comments')),
            'user' => new UserJunctionResource($this->whenLoaded('user')),
        ];
    }
}

<?php

namespace Weap\Junction\Tests\TestSupport\Resources;

use Weap\Junction\Http\Resources\BaseResource;

class GatedPostResource extends BaseResource
{
    /**
     * @return array<int, string>|null
     */
    protected function availableAttributes(): ?array
    {
        return ['id', 'title'];
    }

    /**
     * @return array<int, string>|null
     */
    protected function availableAccessors(): ?array
    {
        return ['excerpt'];
    }

    /**
     * @return array<string, class-string<BaseResource>>|null
     */
    protected function availableRelations(): ?array
    {
        return ['user' => BaseResource::class];
    }
}

<?php

namespace Weap\Junction\Http\Requests;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Weap\Junction\Support\PluckedFields;

/**
 * @method array<int, string>|string|null getPluckFields()
 * @method array<int, string>|string|null getAccessors()
 * @method array<int, string>|string|null getRelations()
 */
class JunctionRequest extends Request
{
    /**
     * The plucked fields selection for this request.
     */
    protected ?PluckedFields $cachedPluckedFields = null;

    public function pluckedFields(): PluckedFields
    {
        return $this->cachedPluckedFields ??= new PluckedFields(
            PluckedFields::tree($this->selectedAttributePaths()),
            PluckedFields::tree($this->getRelations()),
        );
    }

    /**
     * The paths `pluck` selected, together with the accessors `appends` asked for:
     * requesting an accessor is also a request to return it.
     *
     * Null when `pluck` imposed no restriction at all, so `appends` on its own
     * never narrows the response down to the accessors it names.
     *
     * @return array<int, string>|null
     */
    protected function selectedAttributePaths(): ?array
    {
        $plucked = $this->getPluckFields();

        if ($plucked === null) {
            return null;
        }

        return [...Arr::wrap($plucked), ...Arr::wrap($this->getAccessors())];
    }
}

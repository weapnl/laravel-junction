<?php

namespace Weap\Junction\Http\Controllers\Validators;

use Illuminate\Validation\ValidationException;
use Weap\Junction\Http\Controllers\Controller;

class Appends
{
    /**
     * Every segment before the last must be a relation that the resource of its parent allows. The last segment must be
     * an accessor that the resource of its model allows.
     *
     * @param Controller $controller
     * @param array $appends
     * @return array
     *
     * @throws ValidationException
     */
    public static function validate(Controller $controller, array $appends)
    {
        $appends = collect($appends);

        if ($appends->isEmpty()) {
            return [];
        }

        $invalidAppends = $appends->reject(fn ($append) => is_string($append) && static::isAllowed($controller, $append));

        throw_if($invalidAppends->isNotEmpty(), ValidationException::withMessages([
            'appends' => 'Invalid appends: ' . $invalidAppends->filter(fn ($append) => is_string($append))->join(', '),
        ]));

        return $appends->toArray();
    }

    /**
     * @param Controller $controller
     * @param string $append
     * @return bool
     */
    protected static function isAllowed(Controller $controller, string $append): bool
    {
        $segments = explode('.', $append);
        $accessor = array_pop($segments);

        return FieldPath::resource($controller, $segments)?->allowsAccessor($accessor) ?? false;
    }
}

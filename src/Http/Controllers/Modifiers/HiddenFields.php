<?php

namespace Weap\Junction\Http\Controllers\Modifiers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Enumerable;
use Illuminate\Support\Str;
use Weap\Junction\Http\Controllers\Controller;
use Weap\Junction\Http\Controllers\Resources\BaseResource;
use Weap\Junction\Http\Controllers\Response\Response;

class HiddenFields extends Modifier
{
    /**
     * @param Controller $controller
     * @param Response $response
     */
    public static function apply(Controller $controller, Response $response): void
    {
        $hiddenFields = request()?->input('hidden_fields');

        if (! $hiddenFields) {
            return;
        }

        $response->modify(function (Model $model) use ($hiddenFields) {
            self::traverse($model, $hiddenFields);
        });
    }

    /**
     * @param Model $model
     * @param array $hiddenFields
     */
    public static function traverse(Model $model, array $hiddenFields): void
    {
        foreach ($hiddenFields as $field) {
            $traversed = Str::of($field)->explode('.');

            if ($traversed->count() > 1) {
                $value = self::value($model, $traversed->shift());

                if ($value instanceof Enumerable) {
                    $value->each(function ($model) use ($traversed) {
                        self::traverse($model, [$traversed->join('.')]);
                    });
                } elseif ($value instanceof Model) {
                    self::traverse($value, [$traversed->join('.')]);
                }
            } elseif ($model instanceof Enumerable) {
                $model->each->makeHidden($field);
            } else {
                $model->makeHidden($field);
            }
        }
    }

    /**
     * Only a loaded relation or an accessor is read, so a request can not make the model call one of its other methods.
     * Reading an `Attribute` accessor that has a setter can store its value in the model's attributes under the requested
     * name, where the response would return it, so such an accessor is not read. The name must also be a plain name of an
     * accessor that is not hidden.
     *
     * @param Model $model
     * @param string $field
     * @return mixed
     */
    protected static function value(Model $model, string $field): mixed
    {
        if ($model->relationLoaded($field)) {
            return $model->getRelation($field);
        }

        if (! BaseResource::isPlainName($field) || BaseResource::isHiddenOn($model, $field) || $model->hasAttributeSetMutator($field)) {
            return null;
        }

        if ($model->hasGetMutator($field) || $model->hasAttributeGetMutator($field)) {
            return $model->getAttribute($field);
        }

        return null;
    }
}

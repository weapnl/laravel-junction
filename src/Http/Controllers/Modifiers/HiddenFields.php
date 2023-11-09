<?php

namespace Weap\Junction\Http\Controllers\Modifiers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Enumerable;
use Illuminate\Support\Str;
use Weap\Junction\Http\Controllers\Controller;
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
                $relation = $traversed->shift();

                if ($model->$relation instanceof Enumerable) {
                    $model->$relation->each(function ($model) use ($traversed) {
                        self::traverse($model, [$traversed->join('.')]);
                    });
                } elseif ($model->$relation instanceof Model) {
                    self::traverse($model->$relation, [$traversed->join('.')]);
                }
            } else if ($model instanceof Enumerable) {
                $model->each->makeHidden($field);
            } else {
                $model->makeHidden($field);
            }
        }
    }
}

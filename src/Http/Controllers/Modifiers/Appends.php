<?php

namespace Weap\Junction\Http\Controllers\Modifiers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Enumerable;
use Illuminate\Support\Str;
use Weap\Junction\Http\Controllers\Controller;
use Weap\Junction\Http\Controllers\Response\Response;
use Weap\Junction\Http\Controllers\Validators\Appends as AppendsValidator;

class Appends extends Modifier
{
    /**
     * @param Controller $controller
     * @param Response $response
     */
    public static function apply(Controller $controller, Response $response): void
    {
        $appends = request()?->getAccessors();

        if (! $appends) {
            return;
        }

        $appends = AppendsValidator::validate($controller, $appends);

        $response->modify(function (Model $model) use ($appends) {
            self::traverse($model, $appends);
        });
    }

    /**
     * @param Model $model
     * @param array $fields
     */
    public static function traverse(Model $model, array $fields): void
    {
        foreach ($fields as $field) {
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
            } else {
                if ($model instanceof Enumerable) {
                    $model->each->append($field);
                } else {
                    $model->append($field);
                }
            }
        }
    }
}

<?php

namespace Weap\Junction\Http\Controllers\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionUnionType;

class RelationFinder
{
    /**
     * Get the relation with the given name, without calling a method that is not declared to return a relation.
     *
     * @param Model $model
     * @param string $name
     * @return Relation|null
     */
    public static function find(Model $model, string $name): ?Relation
    {
        if (! static::isRelation($model, $name)) {
            return null;
        }

        $relation = Relation::noConstraints(fn () => $model->$name());

        return $relation instanceof Relation ? $relation : null;
    }

    /**
     * Determine whether every segment of the dotted path is a relation of the model before it. The model behind a
     * `morphTo` is only known per record, so a path can not continue past one.
     *
     * @param Model $model
     * @param string $path
     * @return bool
     */
    public static function isRelationPath(Model $model, string $path): bool
    {
        $segments = explode('.', $path);

        foreach ($segments as $index => $segment) {
            $relation = static::find($model, $segment);

            if (! $relation) {
                return false;
            }

            if ($relation instanceof MorphTo) {
                return $index === array_key_last($segments);
            }

            $model = $relation->getRelated();
        }

        return true;
    }

    /**
     * @param Model $model
     * @param string $name
     * @return bool
     */
    public static function isRelation(Model $model, string $name): bool
    {
        if ($name === '') {
            return false;
        }

        // Without a method, only a relation added with `resolveRelationUsing()` can have this name.
        if (! method_exists($model, $name)) {
            return $model->isRelation($name);
        }

        $method = new ReflectionMethod($model, $name);

        if (! $method->isPublic() || $method->isStatic() || $method->getNumberOfRequiredParameters() > 0) {
            return false;
        }

        $returnType = $method->getReturnType();

        $types = match (true) {
            $returnType instanceof ReflectionNamedType => [$returnType],
            $returnType instanceof ReflectionUnionType => $returnType->getTypes(),
            default => [],
        };

        if ($types === []) {
            return false;
        }

        foreach ($types as $type) {
            if (! $type instanceof ReflectionNamedType || ! is_a($type->getName(), Relation::class, true)) {
                return false;
            }
        }

        return true;
    }
}

<?php

namespace Weap\Junction\Http\Controllers\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Weap\Junction\Http\Controllers\Controller;
use Weap\Junction\Http\Controllers\Validators\Relations as RelationsValidator;

class Count extends Filter
{
    /**
     * @param Controller $controller
     * @param Builder<Model>|Relation<Model, Model, mixed> $query
     */
    public static function apply(Controller $controller, Builder|Relation $query): void
    {
        $relations = request()->input('count');

        if (! $relations || ! is_array($relations)) {
            return;
        }

        foreach (RelationsValidator::validate($controller, $relations) as $relation => $constraint) {
            static::traverse(
                $query,
                is_string($relation) ? $relation : $constraint,
                is_string($relation) ? $constraint : null,
            );
        }
    }

    /**
     * @param Builder<Model>|Relation<Model, Model, mixed> $query
     * @param string $relation
     * @param callable|null $constraint
     * @return void
     */
    protected static function traverse(Builder|Relation $query, string $relation, ?callable $constraint = null): void
    {
        $relationParts = explode('.', $relation);
        $countRelation = array_pop($relationParts);

        $count = $constraint ? [$countRelation => $constraint] : [$countRelation];

        // If there's no relation path (single relation name), apply the count on the query itself and exit early.
        if (! $relationParts) {
            $query->withCount($count);

            return;
        }

        static::applyCount($query, $relationParts, $count);
    }

    /**
     * Apply the count on the relation the given relation parts point to. That relation is eager loaded to make the
     * count available in the response, keeping the constraints which are already applied to it intact.
     *
     * @param Builder<Model>|Relation<Model, Model, mixed> $query
     * @param array<int, string> $relationParts
     * @param array<int|string, callable|string> $count
     * @return void
     */
    protected static function applyCount(Builder|Relation $query, array $relationParts, array $count): void
    {
        $relation = array_shift($relationParts);

        $eagerLoads = $query->getEagerLoads();
        $constraint = $eagerLoads[$relation] ?? null;

        $eagerLoads[$relation] = function (Builder|Relation $query) use ($constraint, $relationParts, $count) {
            if ($constraint) {
                $constraint($query);
            }

            if ($relationParts) {
                static::applyCount($query, $relationParts, $count);

                return;
            }

            $query->withCount($count);
        };

        $query->setEagerLoads($eagerLoads);
    }
}

<?php

namespace Weap\Junction\Http\Controllers;

use Closure;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\Controller as BaseController;
use Weap\Junction\Http\Controllers\Requests\DefaultFormRequest;
use Weap\Junction\Http\Controllers\Resources\BaseResource;
use Weap\Junction\Http\Controllers\Traits\HasAction;
use Weap\Junction\Http\Controllers\Traits\HasDestroy;
use Weap\Junction\Http\Controllers\Traits\HasIndex;
use Weap\Junction\Http\Controllers\Traits\HasMedia;
use Weap\Junction\Http\Controllers\Traits\HasShow;
use Weap\Junction\Http\Controllers\Traits\HasStore;
use Weap\Junction\Http\Controllers\Traits\HasUpdate;

class Controller extends BaseController
{
    use HasIndex,
        HasShow,
        HasStore,
        HasUpdate,
        HasDestroy,
        HasAction,
        HasMedia;

    /**
     * The class name of the model for which the controller should implement CRUD actions.
     *
     * @var class-string<Model>
     */
    public string $model;

    /**
     * Determine whether to use the policy corresponding with the model.
     *
     * @var bool
     */
    public bool $usePolicy = false;

    /**
     * The class name of the form request to be used for the store and update methods.
     *
     * @var class-string<FormRequest>
     */
    public string $formRequest = DefaultFormRequest::class;

    /**
     * The class name of the resource to be used for the index and show methods.
     *
     * @var class-string<BaseResource>
     */
    public string $resource = BaseResource::class;

    /**
     * Set to true to save fillable instead of validated attributes in the store and update methods.
     *
     * @var bool
     */
    public bool $saveFillable = false;

    /**
     * Set to true to force simple pagination in the index method.
     *
     * @var bool
     */
    public bool $forceSimplePagination = false;

    /**
     * @param class-string<Model>|null $model
     *
     * @throws Exception
     */
    public function __construct(?string $model = null)
    {
        if (! isset($this->model) && ! $model) {
            throw new Exception('Your controller should contain a property `model` to define which model to query for.');
        }

        $this->model ??= $model;
    }

    /**
     * Define the relations which can be loaded in a request using "dot" notation.
     *
     * Each entry is either:
     * - a relation name (string value with an integer key).
     * - a relation name mapped to a closure that constrains the relation's query (string key with a Closure value).
     *
     * Both forms may be mixed.
     *
     * @return array<int|string, string|Closure>
     */
    public function relations(): array
    {
        return [];
    }

    /**
     * Define the searchable column which can be searched trough in a request using "dot" notation.
     *
     * @return array<int, string>
     */
    public function searchable(): array
    {
        return [];
    }

    /**
     * Define validation rules for the store and update methods.
     *
     * @return array<string, mixed>
     *
     * @deprecated Unused method
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Define validation rule messages for the store and update methods.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [];
    }

    /**
     * Mutate the search value from the client for this particular model.
     *
     * @param string $searchValue The value the user searched for.
     * @return string The mutated search value.
     */
    public function mutateSearchValue(string $searchValue): string
    {
        return $searchValue;
    }
}

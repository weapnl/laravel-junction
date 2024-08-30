<?php

namespace Weap\Junction\Http\Controllers;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Controller as BaseController;
use Weap\Junction\Http\Controllers\Requests\DefaultFormRequest;
use Weap\Junction\Http\Controllers\Resources\BaseResource;
use Weap\Junction\Http\Controllers\Traits\HasAction;
use Weap\Junction\Http\Controllers\Traits\HasDestroy;
use Weap\Junction\Http\Controllers\Traits\HasIndex;
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
        HasAction;

    /**
     * The class name of the model for which the controller should implement CRUD actions.
     *
     * @var string
     */
    public $model;

    /**
     * Determine whether to use the policy corresponding with the model.
     *
     * @var bool
     */
    public $usePolicy = false;

    /**
     * The class name of FormRequest to be used for the store and update methods.
     *
     * @var string
     */
    public $formRequest = DefaultFormRequest::class;

    /**
     * The class name of Resource to be used for the show and index methods.
     *
     * @var string
     */
    protected $resource = BaseResource::class;

    /**
     * Set to true to save fillable instead of validated attributes in store/update methods.
     *
     * @var bool
     */
    protected $saveFillable = false;

    /**
     * Set to true to force simple pagination in index method.
     *
     * @var bool
     */
    protected bool $forceSimplePagination = false;

    /**
     * @param null $model
     *
     * @throws Exception
     */
    public function __construct($model = null)
    {
        $this->model = $this->model ?? $model;

        if (! $this->model) {
            throw new Exception('Your controller should contain a property `model` to define which model to query for.');
        }
    }

    /**
     * Define the relations which can be loaded in a request using "array" notation.
     *
     * @return array
     */
    public function relations()
    {
        return [];
    }

    /**
     * Define the searchable column which can be searched trough in a request using "array" notation.
     *
     * @return array
     */
    public function searchable()
    {
        return [];
    }

    /**
     * Define validation rules for store and update requests.
     *
     * @return array
     */
    public function rules()
    {
        return [];
    }

    /**
     * Define validation rule messages for store and update requests.
     *
     * @return array
     */
    public function messages()
    {
        return [];
    }

    /**
     * @param Model $model
     * @param string $key
     * @return string
     */
    public function getFilePath(Model $model, string $key)
    {
        return '';
    }

    /**
     * @return string
     */
    public function getFileDisk()
    {
        return null;
    }

    /**
     * @param array $files
     * @param Model $model
     */
    public function storeFiles($files, Model $model)
    {
        foreach ($files as $key => $value) {
            $path = $value->store($this->getFilePath($model, $key), $this->getFileDisk());

            $model->$key = $path;
        }

        $model->save();
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

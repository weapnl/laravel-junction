# Upgrade guide

## From v0 to v1

### Requirements

- Laravel 12 or higher

### Moved and renamed classes

Several classes were relocated to give the package a cleaner structure, and one enum lost its redundant `Enum` suffix. The class logic is unchanged — only the namespaces moved. Update any `use` statements (and `extends`/`class-string` references) that point at the old locations:

```php
// Controller traits moved from `Traits` to `Concerns`
Weap\Junction\Http\Controllers\Traits\HasAction         →  Weap\Junction\Http\Controllers\Concerns\HasAction
Weap\Junction\Http\Controllers\Traits\HasIndex          →  Weap\Junction\Http\Controllers\Concerns\HasIndex
Weap\Junction\Http\Controllers\Traits\HasShow           →  Weap\Junction\Http\Controllers\Concerns\HasShow
Weap\Junction\Http\Controllers\Traits\HasStore          →  Weap\Junction\Http\Controllers\Concerns\HasStore
Weap\Junction\Http\Controllers\Traits\HasUpdate         →  Weap\Junction\Http\Controllers\Concerns\HasUpdate
Weap\Junction\Http\Controllers\Traits\HasDestroy        →  Weap\Junction\Http\Controllers\Concerns\HasDestroy
Weap\Junction\Http\Controllers\Traits\HasMedia          →  Weap\Junction\Http\Controllers\Concerns\HasMedia

// Form request and resource lifted out of `Http\Controllers` into the standard `Http` layer
Weap\Junction\Http\Controllers\Requests\DefaultFormRequest  →  Weap\Junction\Http\Requests\DefaultFormRequest
Weap\Junction\Http\Controllers\Resources\BaseResource       →  Weap\Junction\Http\Resources\BaseResource

// Model trait moved next to models
Weap\Junction\Http\Controllers\Traits\HasDefaultAppends  →  Weap\Junction\Models\Concerns\HasDefaultAppends

// Supporting classes moved out of `Http\Controllers`
Weap\Junction\Http\Controllers\Helpers\Database  →  Weap\Junction\Support\Database
Weap\Junction\Http\Controllers\Helpers\Table     →  Weap\Junction\Support\Table

// Enum moved and renamed (dropped the redundant `Enum` suffix)
Weap\Junction\Http\Controllers\Enums\DatabaseTransactionTypeEnum  →  Weap\Junction\Enums\DatabaseTransactionType
```

### Removed methods

The following methods were removed. If you overrode any of them, the override is now dead code and can be deleted:

- `Controller::rules()` was unused by the package. Define your validation rules on your own `FormRequest` (`rules()`), as shown in the README.
- `Controller::messages()` the unused companion to `rules()`. Define validation messages on your own `FormRequest` instead.
- `Weap\Junction\Support\Table::getRelationTableName()` was `@deprecated` because it returned the wrong table name for `morphTo` relations. Resolve the relation with `Table::getRelation()` and read the table name from there.
- `Weap\Junction\Junction::resource()` was `@deprecated` because it is replaced by `Illuminate\Support\Facades\Route::junctionResource()`.

### Type Hints

Native parameter, return, and property types have been added throughout the package. PHP enforces that an overriding method's signature stays compatible with its parent, so if you have extended any of the classes or traits below and overridden one of these methods (or declared one of these properties), you **must** update your signature to match, otherwise PHP will throw a fatal error.

The docblock-only changes (e.g. `array` → `array<string, mixed>`, `Builder` → `Builder<Model>`) are not enforced at runtime, but adopting them keeps your project passing static analysis.

#### Controller

If your controller extends `Weap\Junction\Http\Controllers\Controller`, update any properties and methods you have overridden:

```php
// Properties — these are now typed (and $resource / $saveFillable are now public)
public $model;                          →  public string $model;
public $usePolicy = false;              →  public bool $usePolicy = false;
public $formRequest = /* ... */;        →  public string $formRequest = /* ... */;
protected $resource = /* ... */;        →  public string $resource = /* ... */;
protected $saveFillable = false;        →  public bool $saveFillable = false;

// Methods — return types added
public function relations()             →  public function relations(): array
public function searchable()            →  public function searchable(): array

// Constructor — the $model parameter is now typed
public function __construct($model = null)  →  public function __construct(?string $model = null)
```

`$resource` and `$saveFillable` were widened from `protected` to `public`. If your controller redeclares either as `protected`, change it to `public` — PHP does not allow narrowing a property's visibility in a subclass, so leaving it `protected` will throw a fatal error.

Most controllers set the `$model` property rather than override the constructor, so this usually needs no action. If you *do* override `__construct()`, keep the parameter compatible (an untyped `$model` still works) and pass a model **class-string**. Passing anything other than a string or `null` now throws a `TypeError`.

#### Controller traits (hook methods)

The lifecycle hooks in the `HasIndex`, `HasShow`, `HasStore`, `HasUpdate`, and `HasDestroy` traits now declare parameter and return types. If you override any of them, update the signature:

```php
// HasIndex
public function index()                              →  public function index(): AnonymousResourceCollection
public function beforeIndex(Builder &$query)         →  public function beforeIndex(Builder $query): void
public function afterIndex(Items &$items)            →  public function afterIndex(Items $items): void

// HasShow
public function show($id)                            →  public function show(int|string|Model $id): JsonResource
public function beforeShow(Builder &$query)          →  public function beforeShow(Builder $query): void
public function afterShow(Item &$item)               →  public function afterShow(Item $item): void

// HasStore
public function store()                              →  public function store(): JsonResource
public function beforeStore($valid, $invalid)        →  public function beforeStore(array $validAttributes, array $invalidAttributes): array
public function afterStore($model, $valid, $invalid) →  public function afterStore(Model $model, array $validAttributes, array $invalidAttributes): Model

// HasUpdate
public function update($id)                          →  public function update(int|string|Model $id): JsonResource
public function beforeUpdate($model, $v, $i)         →  public function beforeUpdate(Model $model, array $validAttributes, array $invalidAttributes): array
public function afterUpdate($model, $v, $i)          →  public function afterUpdate(Model $model, array $validAttributes, array $invalidAttributes): Model

// HasDestroy
public function destroy($id)                         →  public function destroy(int|string|Model $id): JsonResource
public function beforeDestroy(Model $model)          →  public function beforeDestroy(Model $model): void
public function afterDestroy(Model $model)           →  public function afterDestroy(Model $model): Model
```

> **Behavioural change:** the `$query` / `$items` parameters on `beforeIndex`, `afterIndex`, `beforeShow`, and `afterShow` are **no longer passed by reference** (the `&` was removed). You can still mutate the builder/response object in place (it is an object), but reassigning the variable to a new instance inside the hook will no longer take effect. If you relied on `$query = ...;` inside one of these hooks, mutate the existing instance instead.

#### Model trait

If your model uses `HasDefaultAppends` and overrides `defaultAppends()`:

```php
public static function defaultAppends()  →  public static function defaultAppends(): array
```

#### Resources

If you extend `Weap\Junction\Http\Resources\BaseResource` and override any of these methods:

```php
public function toArray($request)        →  public function toArray(Request $request): array
public function pluckFields(/* ... */)   →  public function pluckFields(?array $pluckAttributes = null, ?array $pluckAccessors = null, ?array $pluckRelations = null): static
protected function availableAttributes() →  protected function availableAttributes(): ?array
protected function availableAccessors()  →  protected function availableAccessors(): ?array
protected function availableRelations()  →  protected function availableRelations(): ?array
```

`pluckFields()` now returns `static` instead of `$this` (a docblock change only, no code change required, but note it if you type-hint the return value).

`Controller::$resource` now defaults to the new `Weap\Junction\Http\Resources\JunctionResource` instead of `BaseResource`, so controllers that never declared a `$resource` switch over automatically. `JunctionResource` renders the same output for the same request, with these exceptions:

- the model's `$appends` are honored without the `HasDefaultAppends` trait, as in plain Laravel (`BaseResource` only honored them through that trait);
- a `pluck` selection narrows the model's `$appends`, where `BaseResource` returned a `defaultAppends()` accessor whatever the client asked for. `pluck[]=name` on a model appending `slug` no longer answers with the slug; name it in `pluck` or in `appends` to keep it. Only affects models using the `HasDefaultAppends` trait, since that is the only way `BaseResource` returned an appended accessor at all;
- a field in the model's `$hidden` can no longer be exposed by plucking it;
- plucking an accessor no longer resolves it — request it through `appends` instead;
- plucking a field that does not exist no longer adds a `null` entry to the response;
- the store, update and destroy routes honor the field selection too, so a client that sends `pluck` alongside the attributes it writes gets a narrowed response where it previously got the model in full.

If you depend on any of those, pin the old behavior by setting `public string $resource = BaseResource::class;` (or your own subclass of it) on the controller. Note that `BaseResource` is deprecated and will be removed in a future major release.

A `JunctionResource` renders what the model carries: it never loads a relation or resolves an accessor by itself. On the index and show routes Junction resolves the request first — appending the accessors `appends` asked for and eager loading the relations `with` asked for — so a resource that writes its own `toArray()` guards them with Laravel's own helpers:

```php
class PostResource extends JunctionResource
{
    public function toArray(Request $request): array
    {
        return [
            'title' => $this->title,

            // Returned when `appends[]=excerpt` appended it to the model.
            'excerpt' => $this->whenAppended('excerpt'),

            // Returned when `with[]=user` eager loaded it.
            'user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}
```

If you already guard relations with `whenLoaded()`, that keeps working unchanged. One addition: a relation is now only returned when `with` asked for it, so a relation that is loaded for another reason no longer reaches the response — including the ones Junction eager loads for an accessor declared with `Junction::makeAttribute(with: [...])`.

Nothing is appended or eager loaded on the store, update and destroy routes, in a custom action, or in a resource you render by hand, so a guarded accessor or relation is omitted there unless you append or eager load it yourself first.

#### Field selection casing

The `getPluckFields()`, `getAccessors()` and `getRelations()` request macros now normalize the paths they return, so `pluck`, `appends` and `with` no longer depend on the casing the client sends. Per dot separated path, every segment but the last is treated as a relation and camelCased, and the last segment is treated as an attribute or accessor and snake_cased.

This affects both resource classes, because the normalization happens in the request layer. Two things to check in your front-end:

- If it sends camelCase accessors (`appends[]=authorName`) it used to read the value back under `authorName`; the key is now `author_name`.
- If it sends camelCase attributes (`pluck[]=userId`) it used to receive `"userId": null`; it now receives the real `user_id` value.

If a model has a relation method that is genuinely snake_cased, rename it to camelCase (the Laravel convention) or list the camelCase form in the controller's `relations()`.

#### Custom filters

If you have written a custom filter extending `Weap\Junction\Http\Controllers\Filters\Filter`, the abstract `apply()` signature is unchanged at the PHP level (it was already `apply(Controller $controller, Builder|Relation $query): void`). Only the docblock generics were tightened to `Builder<Model>|Relation<Model, Model, mixed>`; no code change is required.

#### Response objects

The fluent methods on `Response`, `Item`, and `Items` now return `static` instead of `self`/`$this`. This is backwards compatible for callers, but if you extended these classes and overrode `modify()`, update the return type to `static`.

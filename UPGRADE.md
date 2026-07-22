# Upgrade guide

## From v0 to v1

### Requirements

- Laravel 12 or higher

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
public function rules()                 →  public function rules(): array        // now @deprecated (unused)
public function messages()              →  public function messages(): array

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
public function store()                              →  public function store(): JsonResponse
public function beforeStore($valid, $invalid)        →  public function beforeStore(array $validAttributes, array $invalidAttributes): array
public function afterStore($model, $valid, $invalid) →  public function afterStore(Model $model, array $validAttributes, array $invalidAttributes): Model

// HasUpdate
public function update($id)                          →  public function update(int|string|Model $id): JsonResponse
public function beforeUpdate($model, $v, $i)         →  public function beforeUpdate(Model $model, array $validAttributes, array $invalidAttributes): array
public function afterUpdate($model, $v, $i)          →  public function afterUpdate(Model $model, array $validAttributes, array $invalidAttributes): Model

// HasDestroy
public function destroy($id)                         →  public function destroy(int|string|Model $id): JsonResponse
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

If you extend `Weap\Junction\Http\Controllers\Resources\BaseResource` and override any of these methods:

```php
public function toArray($request)        →  public function toArray(Request $request): array
public function pluckFields(/* ... */)   →  public function pluckFields(?array $pluckAttributes = null, ?array $pluckAccessors = null, ?array $pluckRelations = null): static
protected function availableAttributes() →  protected function availableAttributes(): ?array
protected function availableAccessors()  →  protected function availableAccessors(): ?array
protected function availableRelations()  →  protected function availableRelations(): ?array
```

`pluckFields()` now returns `static` instead of `$this` (a docblock change only, no code change required, but note it if you type-hint the return value).

#### Custom filters

If you have written a custom filter extending `Weap\Junction\Http\Controllers\Filters\Filter`, the abstract `apply()` signature is unchanged at the PHP level (it was already `apply(Controller $controller, Builder|Relation $query): void`). Only the docblock generics were tightened to `Builder<Model>|Relation<Model, Model, mixed>`; no code change is required.

#### Response objects

The fluent methods on `Response`, `Item`, and `Items` now return `static` instead of `self`/`$this`. This is backwards compatible for callers, but if you extended these classes and overrode `modify()`, update the return type to `static`.

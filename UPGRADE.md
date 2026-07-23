# Upgrade guide

## From v0 to v1

### Requirements

- Laravel 12 or higher

### Type Hints

Native parameter, return, and property types have been added throughout the package. PHP enforces that an overriding method's signature stays compatible with the one it inherits, so if you have extended any of the classes below and overridden one of these methods (or redeclared one of these properties), you **must** update your signature to match, otherwise PHP will throw a fatal error.

The docblock-only changes (e.g. `array` → `array<string, mixed>`, `Builder` → `Builder<Model>`) are not enforced at runtime, but adopting them keeps your project passing static analysis.

#### Controller

If your controller extends `Weap\Junction\Http\Controllers\Controller`, update any properties and methods you have overridden:

```php
// Properties — these are now typed (and $resource / $saveFillable / $forceSimplePagination are now public)
public $model;                                  →  public string $model;
public $usePolicy = false;                      →  public bool $usePolicy = false;
public $formRequest = /* ... */;                →  public string $formRequest = /* ... */;
protected $resource = /* ... */;                →  public string $resource = /* ... */;
protected $saveFillable = false;                →  public bool $saveFillable = false;
protected bool $forceSimplePagination = false;  →  public bool $forceSimplePagination = false;

// Methods — return types added
public function relations()             →  public function relations(): array
public function searchable()            →  public function searchable(): array
public function rules()                 →  public function rules(): array        // now @deprecated (unused)
public function messages()              →  public function messages(): array     // now @deprecated (unused)

// Constructor — the $model parameter is now typed
public function __construct($model = null)  →  public function __construct(?string $model = null)
```

`$resource`, `$saveFillable` and `$forceSimplePagination` were widened from `protected` to `public`. If your controller redeclares any of them as `protected`, change it to `public` — PHP does not allow narrowing a property's visibility in a subclass, so leaving it `protected` will throw a fatal error.

Most controllers set the `$model` property rather than override the constructor, so this usually needs no action. If you *do* override `__construct()`, keep the parameter compatible (an untyped `$model` still works) and pass a model **class-string**. Passing a value that is neither `null` nor coercible to a string now throws a `TypeError`.

#### Controller traits (hook methods)

The lifecycle hooks in the `HasIndex`, `HasShow`, `HasStore`, `HasUpdate`, and `HasDestroy` traits now declare parameter and return types. If you override any of them, update the signature:

```php
// HasIndex
public function index()                              →  public function index(): AnonymousResourceCollection
public function beforeIndex(Builder &$query)         →  public function beforeIndex(Builder $query): void
public function afterIndex(Items &$items)            →  public function afterIndex(Items $items): void

// HasShow
public function show($id)                            →  public function show(int|string|Model $id): BaseResource
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

#### Custom actions

The `HasAction` trait is also composed into `Controller`. Your `actionSomeName()` methods are unaffected, but if you override any of the trait's own methods — most commonly `getActions()`, to restrict which actions are exposed — update the signature:

```php
public function action()                   →  public function action(): mixed
protected function getActionMethod($name)  →  protected function getActionMethod(string $name): ?string
protected function getActions()            →  protected function getActions(): Collection
protected function getActionMethods()      →  protected function getActionMethods(): Collection
```

`getActionMethod()` is now declared `?string`. Its runtime behaviour is unchanged — it always returned a string — but its old docblock incorrectly claimed `Illuminate\Support\Stringable`. If you wrote code against that docblock and called `Stringable` methods on the result, it was already broken.

#### Model trait

If your model uses `HasDefaultAppends` and overrides `defaultAppends()`, no change is required — a method defined on your own class replaces the trait's without a compatibility check. Adding the return type is still recommended for consistency:

```php
public static function defaultAppends()  →  public static function defaultAppends(): array
```

#### Form requests

If your form request extends `Weap\Junction\Http\Controllers\Requests\DefaultFormRequest` (as the README suggests) and overrides either of these hooks, add the `void` return type:

```php
protected function failedValidation(Validator $validator)  →  protected function failedValidation(Validator $validator): void
protected function passedValidation()                      →  protected function passedValidation(): void
```

Your `rules()` and `messages()` methods are unaffected — `DefaultFormRequest` does not declare them.

#### Custom filters

Two internal helpers on the built-in filters gained types, which only matters if you extended one of those classes and overrode them:

```php
// Filters\Relations
protected static function getAccessorRelations(string $modelClass, array $accessors)  →  /* ... */: array

// Filters\Wheres
protected static function applyWhere($query, /* ... */)  →  protected static function applyWhere(Builder|Relation $query, /* ... */)
```

#### Validators

`Validators\Appends::validate()` and `Validators\Relations::validate()` now declare `: array`. These are called internally by the filters; update the return type if you extended either class.

#### Response objects

The instance methods on `Response`, `Item`, and `Items` now return `static` instead of `self`/`$this`, and the `Item::model()` factory declares a return type where it previously had none. This is backwards compatible for callers, but if you extended these classes and overrode any of them, update the overridden return types to match:

```php
// Response
abstract public function modify(Closure $param): self  →  abstract public function modify(Closure $param): static

// Item
public static function model(Model $model)             →  public static function model(Model $model): self
public function modify(Closure $param): self           →  public function modify(Closure $param): static

// Items
public function simplePagination(bool $s): Items       →  public function simplePagination(bool $simplePagination): static
public function enforceOrderByModelKey(/* ... */): Items  →  public function enforceOrderByModelKey(bool $enforceOrderByModelKey, ?string $direction = 'asc'): static
public function get(): self                            →  public function get(): static
public function modify(Closure $param): self           →  public function modify(Closure $param): static
```

`Items::page()` also takes a typed `int $perPage` now. It is `protected` and only called internally, so this only affects you if you overrode it.

#### Route helper

`Junction::resource()` (already `@deprecated` in favour of `Route::junctionResource()`) now declares `resource(string $uri, string $controller, array $only = [/* ... */]): void`. Callers passing anything other than a string URI, a string controller class, and an array of route names will now get a `TypeError`.

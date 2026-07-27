# Changelog

## Unreleased
- Bumped `laravel/pint` composer dev package to `^1.29`.
- Added `pestphp/pest`, `pestphp/pest-plugin-laravel` and `orchestra/testbench` composer packages for testing.
- Added `larastan/larastan` and `phpstan/extension-installer` composer packages for static code analysis.
- Added config for Pest and Orchestra Testbench to support writing tests.
- Added github action to run pest tests.
- Added github action to run PHPStan static code analysis.
- Added native parameter, return, and property type declarations (and stricter PHPDoc generics) throughout the package.
- Added `.gitattributes` (`export-ignore`) so dist tarballs no longer ship dev/CI files, and an `.editorconfig` for consistent formatting.
- Config is now merged via `mergeConfigFrom()` and publishable under the `config` tag (`php artisan vendor:publish --tag=config`).
- Added feature/unit tests for the whole codebase.
- Added `\Weap\Junction\Http\Resources\JunctionResource`, a new resource base class built on standard Laravel resource mechanisms and now the default for every controller, and **deprecated** `\Weap\Junction\Http\Resources\BaseResource` (which keeps its old behavior for backwards compatibility, including the `availableAttributes()`/`availableAccessors()`/`availableRelations()` whitelists).
  - The resource resolves the field selection from the request itself rather than having it pushed in by the controller, so it renders the same inside and outside a Junction controller. Each parameter governs one kind of field: `pluck` restricts the attributes returned (all of them when absent), `appends` asks for accessors to be resolved and adds them to that selection, and `with` requests relations. The primary key is always returned, and the model's `$appends` and `$hidden` are respected as in plain Laravel. A `pluck` selection is authoritative once given: it narrows the model's own `$appends` too, so an appended accessor is only returned when it is named in `pluck` or in `appends`.
  - Relations are rendered recursively as nested resources and the field selection cascades into them (`with[]=orders&pluck[]=orders.total`). A level `pluck` does not reach carries no restriction, matching `BaseResource`.
  - The resource renders what the model carries and never loads a relation or resolves an accessor of its own accord, so the field selection costs no queries the index and show routes were not already making. Junction resolves `appends` by appending the accessor to the model and `with` by eager loading the relation, both before the response is rendered.
  - A resource with its own `toArray()` works as it does in Laravel, with Laravel's own conditional helpers: guard accessors with `whenAppended()` and relations with `whenLoaded()`, both of which answer to the model rather than to the request. `pluck` still restricts the plain attribute keys such a resource returns. Relations may be wrapped in a resource or returned raw; either way nested field selection keeps cascading.
  - Field selection applies to every route, including store, update and destroy, and to a resource rendered outside a controller altogether. Nothing is appended or eager loaded on those, so only `pluck` has an effect there unless the caller arranges it.
  - Migration notes, compared to `BaseResource`: the model's `$appends` are now honored without the `HasDefaultAppends` trait, as in plain Laravel (that trait is only consulted by the deprecated `BaseResource`), but a `pluck` selection narrows them where `BaseResource` returned a `defaultAppends()` accessor whatever the client asked for — name it in `pluck` or `appends` to keep it; a hidden field can no longer be exposed by plucking it; plucking an accessor no longer resolves it (request it through `appends`); plucking a field that does not exist no longer adds a `null` entry to the response; relations are only rendered when requested through `with` — one that was loaded for another reason, such as an accessor reading it, stays out of the response; and `pluck` now narrows the store, update and destroy responses too.
  - The resource renders an Eloquent model, since the field selection is resolved against it — which of its keys are attributes, which are accessors, and which of its relations were loaded. Handing it anything else throws a `\Weap\Junction\Http\Resources\Exceptions\InvalidResourceException`, rather than failing further down where the cause is harder to place. A `null` model still renders an empty payload, as Laravel's own `JsonResource` does.
  - Index responses of the new resource are wrapped by `\Weap\Junction\Http\Resources\AnonymousResourceCollection`, which declares the `items` wrapper and the `total`/`page`/`has_next_page` pagination keys via Laravel's `$wrap` and `paginationInformation()`; single resources are returned unwrapped via `$wrap = null`. Both are declared at class level, so the new resources never mutate the application's global `JsonResource::$wrap` state (unlike the deprecated `BaseResource`, whose behavior is unchanged).
  - Store, update, and destroy responses of both resources go through Laravel's standard resource response pipeline, so a resource's `with()`/`additional()` data is now honored on these routes. They still answer with the bare model, unwrapped, and still with a 200 for a store, where Laravel's own pipeline would answer a recently created model with a 201.

### ⚠️ Breaking changes ⚠️
- Removed support for `laravel/framework` versions 8, 9, 10 and 11. The minimum version is now 12.
- The store, update and destroy routes now honor the field selection, where they previously returned the model in full. A client that sends `pluck` alongside the attributes it writes will get a narrowed response. `appends` and `with` change nothing on these routes unless the resource reaches for the accessor or relation in its own `toArray()`, since nothing is eager loaded for them. Only affects `JunctionResource`; the deprecated `BaseResource` still ignores the field selection here.
- The `pluck`, `appends` and `with` parameters are now normalized to Laravel's own conventions, so the response no longer depends on the casing of the request. In every dot separated path each segment but the last is a relation and is camelCased; the last segment is an attribute or accessor and is snake_cased. `with[]=user_posts` and `pluck[]=userPosts.publishedAt` are therefore equivalent to `with[]=userPosts` and `pluck[]=userPosts.published_at`. Previously only accessors accepted both casings (through Laravel's own `Str::camel()` lookup) and the response echoed the casing that was requested, while `pluck` accepted no camelCase at all — `pluck[]=userId` answered `"userId": null` instead of the attribute. Clients that send camelCase and read camelCase keys back need to read the snake_case key instead. Applies to both resource classes, since the normalization happens in the `getPluckFields()`, `getAccessors()` and `getRelations()` request macros.
- Relocated classes for a cleaner structure (logic unchanged, namespaces only). Update your `use` statements if you reference them directly.
    - Controller traits moved from `Http\Controllers\Traits\` to `Http\Controllers\Concerns\`;
    - Form request moved from `Http\Controllers\Requests\DefaultFormRequest` to `Http\Requests\DefaultFormRequest`;
    - Base resource moved from `Http\Controllers\Resources\BaseResource` to `Http\Resources\BaseResource`;
    - Model trait moved from `Http\Controllers\Traits\HasDefaultAppends` to `Models\Concerns\HasDefaultAppends`;
    - Helper classes moved from `Http\Controllers\Helpers\` moved to `Support\`;
    - Enum class moved from `Http\Controllers\Enums\DatabaseTransactionTypeEnum` moved to `Enums\` and was renamed to `DatabaseTransactionType` (dropped the redundant `Enum` suffix).
- Type declarations were added to overridable methods and properties. If you extend `Controller`, its CRUD traits (`HasIndex`, `HasShow`, `HasStore`, `HasUpdate`, `HasDestroy`), `BaseResource`, or the `Response`/`Item`/`Items` objects, overridden signatures and redeclared properties may need updating — and the `beforeIndex`, `afterIndex`, `beforeShow` and `afterShow` hooks no longer receive their argument by reference. See the [upgrade guide](UPGRADE.md) for the full list of changed signatures.
- Removed the unused `Http\Controllers\Controller::rules()` and `Http\Controllers\Controller::messages()` methods.
- Removed the deprecated `Support\Table::getRelationTableName()` and `Junction::resource()` methods.

See the [upgrade guide](UPGRADE.md) for more information.

## v0.6.1
- Fixed a bug where filtering through a self-referential relation (search, where, whereIn, whereNotIn) generated invalid SQL, because the related model's aliased table name (`table as alias`) was used as a column prefix instead of the alias.

## v0.6.0
- Bumped dependencies which fix vulnerability issues.

## v0.5.0
- Added support for configuring a custom model for temporary media uploads via the `media_temporary_upload_model` config option.

## v0.4.8
- Use resource class for store, update, and destroy responses, ensuring consistent output and applying availableAttributes and availableRelations filters.
- Validate that `id` is required for actions expecting a model, preventing 500 TypeErrors and returning a proper 422 validation error instead.

## v0.4.7
- Added Laravel 13 support.

## v0.4.6
- Added morph map handling for `MediaTemporaryUpload` in `DefaultFormRequest` and `HasMedia` to use `getMorphClass()`, ensuring compatibility with alias-based model types.

## v0.4.5
- Fixed bug where `Weap\Junction\Http\Controllers\Resources\BaseResource` would throw an error when the resource instance is null.

## v0.4.4
- Added support for relation extensions.

## v0.4.3
- Fixed a bug where nested relations with mutations would overwrite parent relation mutations in controllers.

## v0.4.2
- Fixed a bug where relations which had mutations defined in a controller would always be loaded, even if they weren't requested.

## v0.4.1
- Fixed a bug where an error was thrown when applying an accessor on a relation which was eager loaded by another accessor.
- Replaced static `Junction::$cachedAttributeRelations` with request-scoped `AttributeRelationCache` for Laravel Octane compatibility.

## v0.4.0
- Fixed bug where eager loads in accessors would not work if no other relations were requested.
- Parent relations are now implicitly allowed when nested relations are defined in the `relations()` method.
- Added use_db_transactions to the config to enable database transactions for several actions.

## v0.3.2
- Fixed bug where eager loads in accessors would only work for relations (not for the root model).

## v0.3.1
- Fixed bug where eager loads in accessors would only work if a closure was given.

## v0.3.0
- Added support for eager loads in accessors.
- Relations of dot-notated *appends* are now eager loaded.

## v0.2.3
- Laravel 12 support.

## v0.2.2
- Fixed bug where isValidMediaFileArray in the DefaultFormRequest could throw an error if the value is not an array.

## v0.2.1
- Fixed bug where prepareForValidation in the DefaultFormRequest could overwrite previous changes made to the input.

## v0.2.0
- Fixed a bug where the S3 disk was not supported for temporary media uploads.
- Added ability to enforce an order by model key on the query in an index route.
- Added missing `ext-pdo` requirement in the composer config.
- Removed table of contents from `README.md` because GitHub has built-in feature for this.
- Added link to js-junction package in `README.md`.
- Added laravel pint github workflow.

## v0.1.2
- Fixed bug where `morphTo` relations in `where`, `whereIn`, `whereNotIn` and `search` filters would throw an error.
- Deprecated `getRelationTableName` method on `Weap\Junction\Http\Controllers\Helpers\Table` class because it gives the wrong results for `morphTo` relations.

## v0.1.1
- Media temporary upload `beforeMediaUpload` & `afterMediaUpload` hooks.
- Media temporary upload bugfix, `$mediaFiles` was not being filled.

## v0.1.0
- Add local development instructions for composer and docker.
- Refactor scope calls to be more DRY.
- Fix checking if an attribute exists.
- Create a hook for the controller to mutate search values (e.g. for date formatting) (https://hitower.atlassian.net/browse/WEAP-187).
- Print any invalid relation names in the exception.
- Laravel Pint integrated.
- Added the Temporary Media Upload functionality.

## v0.0.15
- Return only the pagination keys if the request is paginated.

## v0.0.14
- Added support for simple pagination.

## v0.0.13
- Duplicate route names bug resolved.
- Laravel 11 support.

## v0.0.12
- Added route registrar.
- Search columns bugfix.

## v0.0.11
- Added support for post requests.
- Updated the routing, works with only controller names now.

## v0.0.10
- Fixed license in composer file.

## v0.0.9
- Added license file.

## v0.0.8
- Added option to save fillable instead of validated attributes.

## v0.0.7
- Fixed PHPDoc.
- Fixed readme example for scopes.

## v0.0.6
- Count class bugfix.
- Added support for whereNotIn.

## v0.0.5
- Fixed a bug with the where statement.

## v0.0.4
- Fixed bug where you couldn't use a scope without a parameter.

## v0.0.1
- Initial version.

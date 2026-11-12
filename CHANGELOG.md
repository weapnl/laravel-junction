# Changelog

## Unreleased
- **Breaking:** requires Laravel 10.10.1 or later. The code already used framework features that Laravel 8, 9 and 10.10.0 and older do not have.
- **Breaking:** `appends` are validated at every level of a dotted path, before the query runs. Each relation in the path must be allowed by the resource of its parent model, and the last segment must be an accessor of the model it lands on (never a column) that its resource allows. Appends through a `morphTo` relation or past an accessor are refused with a 422.
- **Breaking:** `pluck` is validated the same way. Hidden fields and relations without a declared return type are refused, and an accessor must be allowed as an append; `availableAttributes()` only limits the columns that can be plucked, and must list a column with the exact name the request uses. The primary key can always be requested, even when it is hidden or not listed in `availableAttributes()`; it is returned when `availableAttributes()` returns `null` or lists it. The response applies the same rules, so `pluck` and `appends` can no longer return hidden columns or accessors a resource does not allow.
- **Breaking:** related models are returned through the resource of their own model. Use `Junction::resolveResourcesUsing()` to map a model to its resource; without a resolver, the base resource is used. A relation that `availableRelations()` maps to `null` is allowed and uses the resolved resource.
- **Breaking:** relations are only recognised in `appends` and `pluck` paths when their method declares a relation as return type.
- Fixed a bug where an append naming a model method (e.g. `save`) called that method on an empty model while looking for relations to eager load.
- **Breaking:** an accessor that is hidden (`$hidden`, or missing from `$visible`) can not be appended or plucked, and is no longer returned through `HasDefaultAppends`. Default appends missing from a non-null `availableAccessors()` are no longer returned either, so a resource that lists its accessors must also list the model's `$appends`. Accessor names are compared ignoring case and underscores, the way PHP finds the accessor method.
- **Breaking:** an `appends` or `pluck` field may only contain letters, digits and underscores, and can not start with a digit. Laravel also treats `-` and spaces as word separators when it looks up an accessor, so `masked-token` reached the accessor of a hidden `masked_token`.
- **Breaking:** a requested relation (`with` and `count`) must match an allowed relation segment by segment; before, any prefix of an allowed relation's name was accepted (`save` for `savedSearches`). With `relations()` returning `['*']`, each segment must be a method that declares a relation as return type, and a path can not continue past a `morphTo` relation.
- Fixed a bug where `with`, `count` and `hidden_fields` could make a model call one of its methods because the request named it.
- **Breaking:** `count` only uses the values of the request parameter. Before, a key was counted as the relation while the validator checked the value (`count[save]=next` checked `next` but called `save()`).
- **Breaking:** the relations in a dotted column of `wheres`, `where_in`, `where_not_in` and `search_columns` must be declared relations, otherwise the request gets a 422. Before, the method of every segment was called (`wheres[0][column]=save.id` called `save()`). The `searchable` columns of the controller are not checked.
- Fixed a 500 error when `pluck` names a relation that is loaded with `with` (`pluck[]=orders&with[]=orders`); the whole relation is now returned through its resource.
- **Breaking:** the relation of a dotted `appends` or `count` is only treated as already requested when `with` contains that relation or a path through it. Before, any relation in `with` whose name started with it counted (`userGroups` for `user.full_name`), so the relation was not checked against `relations()` and the append was left out of the response. Such a request now gets a 422 when `relations()` does not allow the relation. Only the values of `appends` and `count` are used to find these relations, so equal keys in both can no longer hide an append.
- Fixed a bug where the closure of a relation in `relations()` also ran on the query of every relation whose name is a prefix of it (the closure of `userGroups` ran on `user`).
- A resource listing an accessor in `availableAttributes()` but not in a non-null `availableAccessors()` can no longer return that accessor through `pluck`. A hidden column listed in `availableAttributes()` is still returned without `pluck`, but plucking it is refused.
- Added `BaseResource::allowsAccessor()`, `BaseResource::allowsPluckField()`, `BaseResource::resourceForRelation()` and `Controller::getResourceClass()`.

## v0.6.2
- Changed laravel-pint GitHub action to use version from composer instead of always using the latest.
- Added support for dot-notation in the `count` filter (e.g. `count[]=orders.products`), which counts the relation on the last relation in the path. That relation is eager loaded, keeping the relation closures defined in the controller intact.

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

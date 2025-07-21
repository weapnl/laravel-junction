# Changelog

## Unreleased
- Added support for ordering by relationship columns in the Order filter (e.g., `brand.code`, `user.name`).
- Order filter now uses LEFT JOIN approach for relationship ordering to preserve all records.

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

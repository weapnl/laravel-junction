# Changelog

## Unreleased
- Add local development instructions for composer and docker.
- Refactor scope calls to be more DRY.
- Fix checking if an attribute exists.
- Create a hook for the controller to mutate search values (e.g. for date formatting) (https://hitower.atlassian.net/browse/WEAP-187).
- Print any invalid relation names in the exception.
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

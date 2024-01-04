# Changelog

## Unreleased

## v4.1.1
- Fixed bug where you couldn't use a scope without a parameter.

## v4.1.0
- Added ability to only save validated attributes in store/update requests.

## v4.0.0
- Added Laravel 10 support.
- Removed PHP 8.0 support.
- Added PHP 8.2 support.

## v3.1.1
- Fixed a bug where global scopes (like soft deletes) weren't included when using `page_for_id`.
- Fixed a bug where relations weren't correctly validated since `v3.1.0`.

## v3.1.0
- Added ability to add filters to relation queries.

## v3.0.0
- Added Laravel 9 support.
- Removed Laravel 7 support.
- Removed PHP 7 support.

## v2.0.4
- Fixed bug where hidden attributes were included in resources.

## v2.0.3
- Fixed bug in BaseResource where attributes couldn't be loaded when specifying available attributes.

## v2.0.2
- Added `HasDefaultAppends` trait, which can be used in a model to always add default appends to the response.

## v2.0.1
- Fixed bug where relations weren't plucked correctly.

## v2.0.0
- Added MR template.
- Possibly breaking fix: When not using pluck or relations, fields loaded in accessors are no longer returned.
  - Previously, relations loaded in accessors were also returned in the reponse.
- Breaking: The `show` and `index` methods now return a `BaseResource` and `AnonymousResourceCollection` respectively.
- Breaking: The `index` route is now always paginated.
- Upped min PHP version to v7.4.
- Added ability to specify attributes, accessors and relations in the resource.
- Fixed bug where pluck didn't work anymore after v1.4.0.

## v1.4.0
- Added functionality to return resources while using show() and index() route functions.

## v1.3.2
- Now only searching for an id when `page_for_id` is not a falsy value.

## v1.3.1
- Fixed bug (introduced in v1.3.0) where passing an empty value (`''` or `null`) to a scope would cause it to pass through an empty string.

## v1.3.0
- Added ability to pass multiple parameters to scopes.

## v1.2.1
- Fixed bug in BaseResource which would cause accessors to be called twice.

## v1.2.0
- Added `pluck` method so only given attributes are returned in the result. (only available for `index` and `show` routes)
- Added a BaseResource which is used for the new `pluck` method.
- Relations (`where` filter) now also allows snake_case values. 

## v1.1.4
- Added relation table names in the query for the filters `Wheres` and `WhereIns`

## v1.1.3
- Now checking operator when passing `null` as value in a `where` statement.

## v1.1.2
- Fixed bug where passing `null` as a value in a `where` statement caused a query error. Instead, it will now add `where [column] = null` to the query.

## v1.1.1
- Fixed bug where calling an action route without id would always give an unauthorized response (403).
- Fixed an error which was thrown when searching in a column which exists in the table itself and one of the loaded relations (`ambiquous column`).

## v1.1.0
- Fixed bug where getting unvalidated attributes wouldn't work when array was deeper than 1 level.
- Fixed bug which occurred when config `always_paginated` was false.
- Now deploying tags to Gitlab package repository.
- Simplified usage of `action` routes.

## v1.0.1
- Fixed bug where joining a table which has equal column names would return the wrong values.
- Fixed bug where using custom joins caused an SQL error (`column reference "id" is ambiguous`).

## v1.0.0
- **Breaking:** Improved `index`-route performance by paginating results on database-level (#6).
  - The `modify` method of the `Response` class now only allows modification of models which are in the current paginated result set.
  - Removed the `filter` method of the `Response` class.
- Added parameter `page_for_id` to get the page the given ID is on (#5).
- Improved `hidden_fields` modifier to allow dot notation for relation fields.

## v0.7.1
- Fixed bug where some traits tried to access a protected variable.

## v0.7.0
- Added support for FormRequests.

## v0.6.1
- Added support for PHP 8.

## v0.6.0
- Files are now stored after saving the model in the traits HasStore and HasUpdate.

# Laravel-Junction

This project allows you to easily create a REST API with Laravel. It has extended functionality, such as eager loading, searching, filtering, and more.

## Installation
```bash
composer require weapnl/laravel-junction
```

### Js Support
We're excited to announce that this Laravel-Junction package now has a companion JavaScript/TS library! This addition extends the functionality of our Laravel package to the front end, offering a seamless integration for your web applications.

### Development
In order to easily work on this package locally and use it in another local project, do the following:

1. Add a repository in the `composer.json` file of the project you want to include Laravel-Junction in:

```json
"repositories": {
    "laravel-junction": {
        "type": "path",
        "url": "./laravel-junction",
        "options": {
            "symlink": true
        }
    }
}
```

2. If your other project runs in docker, add a volume referencing the folder where Laravel-Junction resides:

```yaml
services:
  api:
    image: ...
    volumes:
      - ../laravel-junction:/var/www/laravel-junction
```

3. Install the local package. The `symlink` option you set on the repository earlier makes sure that you only need to do this once as opposed to every code change.

```bash
composer require weapnl/laravel-junction dev-main
```


## Quick Start
```php
// app/Http/Controllers/API/UserController.php
namespace App\Http\Controllers\API;

use Weap\Junction\Http\Controllers\Controller;

class UserController extends Controller
{
    /**
     * The class name of the model for which the controller should implement CRUD actions.
     *
     * @var string
     */
    public $model = User::class;

    /**
     * Define the relations which can be loaded in a request using "array" notation.
     *
     * @return array
     */
    public function relations(): array
    {
        return [
            'orders',
        ];
    }
```

```php
// routes/api.php
Junction::apiResource('users', 'UserController');
```

You're all set and ready to go now. You can now perform requests to the `/api/users` endpoint. Try a post request to create a new user, or a get request to retrieve all users.

## Usage

### Setting up the Controller

To make the controller accessible through the api, you need to extend the `Weap\Junction\Http\Controllers\Controller` class. This class extends the default Laravel controller, and adds some extra functionality.
Defining the controller is pretty straightforward, check the [Quick start](#quick-start) section for a basic example. We will now go over some of the extra functionality.

```php
// app/Http/Controllers/API/UserController.php
namespace App\Http\Controllers\API;

use Weap\Junction\Http\Controllers\Controller; // Make sure to import the Controller class from the Weap/Junction package.

class UserController extends Controller
{
    /**
     * The class name of the model for which the controller should implement CRUD actions.
     *
     * @var string
     */
    public $model = User::class;
    
    /**
     * The class name of Resource to be used for the show and index methods.
     *
     * @var string $resource
     */
    public $resource = UserResource::class;

    /**
     * Define the relations which can be loaded in a request using "array" notation.
     *
     * @return array
     */
    public function relations(): array
    {
        return [
            'orders',
            // Define all your relations here with should be accessible through the API.
        ];
    }
```


#### Sample usage
```
/api/users?orders[0][column]=id&orders[0][direction]=asc&&search_value=john&search_columns[]=name&search_columns[]=email
```

#### Sample response
The response always contains the properties `items`, `total` and `page`, even if you're not using pagination.
```json
{
  "items": [
    {
      "id": 2,
      "name": "John Doe",
      "email": "john.doe@app.com",
      "orders": [],
      "comments": [
        {
          "id": 1,
          "body": "Hello world!"
        }
      ]
    }
  ],
  "total": 1, // Total amount of items
  "page": 1 // The current page
}
```

#### Filters
Filters are applied to the query. Filters are defined using array keys. Available filters:

| Key              | Example                                                                                            | Description                                                                                   |
|------------------|----------------------------------------------------------------------------------------------------|-----------------------------------------------------------------------------------------------|
| `limit`          | `limit=10`                                                                                         | Limit the maximum amount of results.                                                          |
| `orders`         | `orders[][column]=name,orders[][direction]=asc`                                                    | Columns to order on.                                                                          |
| `with`           | `with=[orders,comments]`                                                                           | Relations to load.                                                                            |
| `scopes`         | `scopes[0][name]=hasName&scopes[0][params][0]=John`                                                | Scopes to apply with the given parameters.                                                    |
| `search_value`   | `search_value=john`                                                                                | Search for the given value.                                                                   |
| `search_columns` | `search_columns[]=id&search_columns[]=name`                                                        | The columns to search in. (optional: defaults to the searchable variable on your controller.) |
| `wheres`         | `wheres[0][column]=name&wheres[0][operator]=%3D&wheres[0][value]=John (%3D = '=', ASCII Encoding)` | Apply where clauses.                                                                          |
| `where_in`       | `where_in[0][column]=id&where_in[0][values][0]=1&where_in[0][values][1]=2`                         | Apply where in clause. (Where id is 1 or 2)                                                   |
| `where_not_in`   | `where_not_in[0][column]=id&where_not_in[0][values][0]=1&where_not_in[0][values][1]=2`             | Apply where not in clause. (Where id is not 1 or 2)                                           |

#### Modifiers
Modifiers are applied after the query has run. Available modifiers:

| Key             | Example                                       | Description                                                                                               |
|-----------------|-----------------------------------------------|-----------------------------------------------------------------------------------------------------------|
| `appends`       | `appends[]=fullname&appends[]=age`            | Add appends to each model in the result.                                                                  |
| `hidden_fields` | `hidden_fields[]=id&hidden_fields[]=address]` | Hide the given fields for each model in the result.                                                       |
| `pluck`         | `pluck[]=id&pluck[]=address.house_number`     | Only return the given fields for each model in the result. (Only available for `index` and `show` routes) |

#### Pagination
Pagination is applied on database-level (after applying all filters). The following parameters can be used to setup pagination:

| Key           | Example         | Description                                                                                                                                                  |
|---------------|-----------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `paginate`    | `paginate=25`   | Paginate the result. This also specifies the amount of items per page.                                                                                       |
| `page`        | `page=1`        | The page to get. Defaults to 1. Requires `paginate` to be set.                                                                                               |
| `page_for_id` | `page_for_id=1` | This will search the correct page based on the given model id. `page` is used as a fallback if the given id can not be found. Requires `paginate` to be set. |

#### Simple pagination
Simple pagination almost the same as the pagination above. But the simple pagination doesn't return the total amount of items or a page number. This is useful for large database tables where the normal pagination is too slow.

| Key                 | Example                  | Description                                         |
|---------------------|--------------------------|-----------------------------------------------------|
| `paginate`          | `paginate=25`            | This specifies the amount of items per page.        |
| `simple_pagination` | `simple_pagination=true` | This defines that simple pagination should be used. |

### Relations
To limit the relations which can be loaded using the `with` filter, you can override the `relations` method on your controller.
This method should return an array containing relations (dot-notation is supported). To add filters to the relation query, you can use the key as relation name and a closure as the value.

**Note**: When using dot-notation, if a closure is given for one of the higher-level relations, that closure will be applied to the query. For example with relations implemented like below, loading the relation `user.activities`, will apply the `isAdmin` scope to the user query.
```php
public function relations()
{
    return [
        'user' => fn($query) => $query->isAdmin(),
        'user.activities',
    ];
}
```

### Search
This package supports search functionality for given models and relations.
On your controller, add a searchable property like defined below.
When you want to search a model, add "search_value" to your request. Optionally you can add "search_columns" to override the columns from your controller.
```php
public $searchable = [
    'id',
    'name',
    'orders.order_number',
];
```

### Resources
To use resources, set the `resource` variable in your controller. Your resource must extend `\Weap\Junction\Http\Controllers\Resources`.

This allows you to specify which attributes, accessors and relations will be returned. To do this, override the corresponding method:
- `availableAttributes`. Return an array of strings, specifying which attributes will be returned. The primary key is always included.
- `availableAccessors`. Return an array of strings, specifying which accessors will be returned.
- `availableRelations`. Return an array of key/value pairs, where the key is the name of the relation, and the value is another resource.

Return `null` in any of these methods to allow `ALL` attributes/accessors/relations to be returned.

Example:
```php
class UserResource extends BaseResource
{
    /**
     * @return array|null
     */
    protected function availableAttributes(): ?array
    {
        return [
            'first_name'
        ];
    }

    /**
     * @return array|null
     */
    protected function availableAccessors(): ?array
    {
        return [
            'fullName'
        ];
    }

    /**
     * @return array|null
     */
    protected function availableRelations(): ?array
    {
        return [
            'orders' => OrderResource::class,
        ];
    }
}
```
### Actions
This package also supports action routes.

Add the action method in your controller:

```php
/**
 * @param null|Model $model
 */
protected function actionSomeName($model = null)
{
    //
}
```

- If you are using policies, your policy should implement the `action` policy, which receives the model as parameter.
- Now, you can call the following route as a `PUT` request: `/api/users`. In the body, add the following (the id is optional):
```json
{
    "action": "someName",
    "id": 1
}
```
- You can add as many actions as you want. Just make sure to prefix the method with `action`.

### Index route options
#### Enforce order by model key
To enable the option to enforce an order by on the query, set the `junction.route.index.enforce_order_by_model_key` config value to `true`.
This will add an "order by" clause to the query based on the model's key name, if no "order by" is already present for the key name.
The default order direction is `asc`, but if you want it to use `desc`, update the `junction.route.index.enforce_order_by_model_key_direction` config value to `desc`.

### Validation

#### FormRequest validation
To validate the incoming request, you can create a `FormRequest` and extend the `Weap\Junction\Http\Controllers\Requests\DefaultFormRequest` class. This class extends the default Laravel `FormRequest` class, and adds some extra functionality.

#### Standard validation
To validate the request, create a request file for your model and add this to the controller.
```php
/**
 * The class name of FormRequest to be used for the store and update methods.
 *
 * @var string
 */
public $formRequest = ModelRequest::class;
```

- Add rules to the `rules()` method in the `ModelRequest`.
```php
/**
 * Get the validation rules that apply to the request.
 *
 * @return array
 */
public function rules()
{
    return [
        'first_name' => 'required',
    ];
}

/**
 * Define validation rule messages for store and update requests.
 *
 * @return array
 */
public function messages()
{
    return [
        'first_name.required' => 'The first name is required.',
    ];
}
```

#### Save fillable attributes
By default, only validated attributes are saved when calling store/update routes. To save fillable attributes instead, set the following on your controller:
```php
/**
 * Set to true to save fillable instead of validated attributes in store/update methods.
 *
 * @var bool
 */
protected $saveFillable = true;
```

### Using Temporary Media Files with [Spatie Medialibrary](https://spatie.be/docs/laravel-medialibrary/v11/introduction)

#### Step 1: Uploading Files via the API
To upload files through the API, use the `/media/upload` endpoint. Include an array of files under the `files` key in the request body. These files will be temporarily stored in the media library and linked to a `MediaTemporaryUpload` model.

**Example Upload Request:**
```json
{
    "files": [<uploaded file here>, ...]
}
```

#### Step 2: Attaching Files to a Model

##### Example A: Updating an Existing Model
To attach files to an existing model, include the media IDs in a `PUT` request. For instance, if you want to attach an identity document to an employee, your `PUT` request to `/employees/3` might look like this, assuming the identity files are stored in the `IdentityFiles` [collection](https://spatie.be/docs/laravel-medialibrary/v11/working-with-media-collections/simple-media-collections) on the `Employee` model:

```json
{
    "first_name": "John",
    "last_name": "Doe",
    "_media": {
        "IdentityFiles": [1]
    }
}
```

The API will search the request body for the `_media` key and associate the specified media IDs with the correct collection. In this example, media ID 1 will be attached to the `IdentityFiles` collection of the `Employee` with ID 3.

##### Example B: Creating a New Model
You can also attach files when creating a new model using a `POST` request. For example, if you are creating a new `Employee` and want to attach a profile picture, your `POST` request to `/employees` might look like this:

```json
{
    "first_name": "Jane",
    "last_name": "Smith",
    "_media": {
        "ProfilePicture": [2]
    }
}
```

This will attach media ID 2 to the `ProfilePicture` collection of the newly created `Employee`.

#### Using the `_media` Key in Nested Structures
The `_media` key can also be nested within the request body, for example, inside a `contact` key. In this case, the API will attach the media files to the `contact` relationship of the `Employee`, whether you are creating a new model or updating an existing one.

**Example Request with Nested `_media` Key (for creation):**
```json
{
    "first_name": "Jane",
    "last_name": "Smith",
    "contact": {
        "phone": "123-456-7890",
        "_media": {
            "ProfilePicture": [3]
        }
    }
}
```

In this example, when creating a new `Employee`, media ID 3 will be attached to the `ProfilePicture` collection within the `contact` relationship of the new `Employee`.

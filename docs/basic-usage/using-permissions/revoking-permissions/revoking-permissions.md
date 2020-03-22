# <u>Revoking Permissions</u>
> The function `revokePermissionTo()` is used to revoke/remove permissions from a user.
#### Description
```php
revokePermissionTo(mixed $permission, [mixed $model = null, mixed $modelId = null], [bool $recursive = false]): $this
```
#### Arguments
- ***$permission***
    - Type : `int` | `string` | `array` | `\Oslllo\Larakey\Contracts\Permission`
    - Description : The permission to be removed from the user.
- ***$model***
    - Type : `string` | `\Illuminate\Database\Eloquent\Model`
    - Description : The model class or instance to be used with the
- ***$modelId***
    - Type : `string` | `int`
    - Description : Used to indicate the id of a model when only a class name string is provided to `$model`. 
    - Note : ***`$model` must be present when this value is used.***
- ***$recursive***
    - Type : `boolean`
    - Description : Determines whether or not to revoke a permission recursively/also remove permissions with a lower scope.
    - More info : [here](basic-usage/using-permissions/revoking-permissions/with-recursion.md)

#### Returns
returns `$this`.

---

## Examples
>Revoke permission from user
```php
// Give permissions
$user->givePermissionTo('edit');
$user->givePermissionTo('edit', Post::class);
$user->givePermissionTo('edit', Post::class, 1);
```
```php
// Revoke permission
$user->revokePermissionTo('edit', '*');
```
```php
// Check permissions
$user->hasPermissionTo('edit'); // FALSE
$user->hasPermissionTo('edit', '*'); // FALSE
$user->hasPermissionTo('edit', Post::class); // TRUE
$user->hasPermissionTo('edit', Post::class, 1); // TRUE
```

!> ⚠️**NOTE:** The user will still have permission to edit `Post::class` and `$post` with id `1`. If you want to include permissions with a lower scope see [revoking permissions with recursion](basic-usage/using-permissions/revoking-permissions/with-recursion.md).

---

>Revoke permission with class from user
```php
// Give permission to class and model instance
$user->givePermissionTo('edit', Post::class);
$user->givePermissionTo('edit', Post::class, 1);
```
```php
// Give class permission
$user->revokePermissionTo('edit', Post::class);
```
```php
// Check permissions
$user->hasPermissionTo('edit'); // FALSE
$user->hasPermissionTo('edit', '*'); // FALSE
$user->hasPermissionTo('edit', Post::class); // TRUE

$post = Post::find(1);
$user->hasPermissionTo('edit', $post); // TRUE
$user->hasPermissionTo('edit', Post::class, $post->id); // TRUE
```

---

>Revoke permission with model instance from user
```php
// Give user permission to model instance
$user->givePermissionTo('edit', Post::class, 1) // OR;

$post = Post::find(1);
$user->givePermissionTo('edit', $post); // OR;
$user->givePermissionTo('edit', Post::class, $post->id);
```
```php
// Check permissions
$user->hasPermissionTo('edit'); // FALSE
$user->hasPermissionTo('edit', '*'); // FALSE
$user->hasPermissionTo('edit', Post::class); // FALSE

$post = Post::find(1);
$user->hasPermissionTo('edit', $post); // TRUE
$user->hasPermissionTo('edit', Post::class, $post->id); // TRUE
```

---

>Give user multiple permission at once
```php
$user->givePermissionTo(['edit', 'delete', 'read']); // OR;
$user->givePermissionTo(['edit', 'delete', 'read'], '*'); // OR;
$user->givePermissionTo(['edit', 'delete', 'read'], Post::class); // OR;

$post = Post::find(1);
$user->givePermissionTo(['edit', 'delete', 'read'], $post); // OR;
$user->givePermissionTo(['edit', 'delete', 'read'], Post::class, $post->id); // OR;
```

---

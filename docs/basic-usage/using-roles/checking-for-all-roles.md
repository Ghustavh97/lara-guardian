# Checking For All Roles
 > The function `hasAllRoles()` can be used to check if a user has all the given roles.
## Description
```php
hasAllRoles(mixed $roles, [string $guard = null]): bool
```
## Arguments
- **$roles**
    - Type : `array` | `string` | `\Oslllo\Larakey\Contracts\Role`
    - Description : The roles to check.
## Returns
    Returns bool.
## Usage
```php
$user->hasAllRoles(Role::all());
```

---
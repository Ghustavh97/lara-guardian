<?php

namespace Oslllo\Larakey;

use Exception;
use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;
use Oslllo\Larakey\Contracts\Role as RoleContract;
use Oslllo\Larakey\Contracts\Permission as PermissionContract;
use Oslllo\Larakey\Padlock\Key;
use Oslllo\Larakey\Padlock\Cache;
use Oslllo\Larakey\Padlock\Gate;
use Oslllo\Larakey\Padlock\Combination;

class LarakeyServiceProvider extends ServiceProvider
{
    /**
     * LarakeyServiceProvider boot function
     *
     * @param \Oslllo\Larakey\Padlock\Gate $gate
     * @param \Oslllo\Larakey\Padlock\Cache $cache
     * @param \Illuminate\Filesystem\Filesystem $filesystem
     * @return void
     */
    public function boot(Gate $gate, Cache $cache, Filesystem $filesystem)
    {
        if (function_exists('config_path')) {
            $this->publishes([
                __DIR__.'/../config/larakey.php' => config_path('larakey.php'),
            ], 'config');

            $larakeyPermissionsTable = __DIR__.'/../database/migrations/create_larakey_permission_tables.php.stub';

            $this->publishes([
                $larakeyPermissionsTable => $this->getMigrationFileName($filesystem),
            ], 'migrations');

            $this->registerMacroHelpers();
        }

        $this->commands([
            Commands\CacheReset::class,
            Commands\CreateRole::class,
            Commands\CreatePermission::class,
            Commands\Show::class,
        ]);

        $this->app->singleton(Larakey::class, function ($app) {
            return new Larakey;
        });

        $this->app->singleton(Cache::class, function ($app) use ($cache) {
            return $cache;
        });

        $this->app->singleton(Gate::class, function ($app) use ($gate) {
            return $gate;
        });

        $this->app->bind(Key::class, function ($app, $parameters) {
            return new Key($parameters['model'], $parameters['permission']);
        });

        $this->app->bind(Combination::class, function ($app, $parameters) {
            return new Combination($parameters['arguments']);
        });

        $this->registerModelBindings();

        $gate->registerPermissions();
    }

    /**
     * LarakeyServiceProvider register function
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/larakey.php',
            'larakey'
        );

        $this->registerBladeExtensions();
    }

    /**
     * Registers model bindings.
     *
     * @return void
     */
    protected function registerModelBindings()
    {
        $config = $this->app->config['larakey.models'];

        $this->app->bind(PermissionContract::class, $config['permission']);
        $this->app->bind(RoleContract::class, $config['role']);
    }

    /**
     * Registers blade extensions.
     *
     * @return void
     */
    protected function registerBladeExtensions()
    {
        $this->app->afterResolving('blade.compiler', function (BladeCompiler $bladeCompiler) {
            $bladeCompiler->directive('role', function ($arguments) {
                list($role, $guard) = explode(',', $arguments.',');

                return "<?php if(auth({$guard})->check() && auth({$guard})->user()->hasRole({$role})): ?>";
            });
            $bladeCompiler->directive('elserole', function ($arguments) {
                list($role, $guard) = explode(',', $arguments.',');

                return "<?php elseif(auth({$guard})->check() && auth({$guard})->user()->hasRole({$role})): ?>";
            });
            $bladeCompiler->directive('endrole', function () {
                return '<?php endif; ?>';
            });

            $bladeCompiler->directive('hasrole', function ($arguments) {
                list($role, $guard) = explode(',', $arguments.',');

                return "<?php if(auth({$guard})->check() && auth({$guard})->user()->hasRole({$role})): ?>";
            });
            $bladeCompiler->directive('endhasrole', function () {
                return '<?php endif; ?>';
            });

            $bladeCompiler->directive('hasanyrole', function ($arguments) {
                list($roles, $guard) = explode(',', $arguments.',');

                return "<?php if(auth({$guard})->check() && auth({$guard})->user()->hasAnyRole({$roles})): ?>";
            });
            $bladeCompiler->directive('endhasanyrole', function () {
                return '<?php endif; ?>';
            });

            $bladeCompiler->directive('hasallroles', function ($arguments) {
                list($roles, $guard) = explode(',', $arguments.',');

                return "<?php if(auth({$guard})->check() && auth({$guard})->user()->hasAllRoles({$roles})): ?>";
            });
            $bladeCompiler->directive('endhasallroles', function () {
                return '<?php endif; ?>';
            });

            $bladeCompiler->directive('unlessrole', function ($arguments) {
                list($role, $guard) = explode(',', $arguments.',');

                return "<?php if(!auth({$guard})->check() || ! auth({$guard})->user()->hasRole({$role})): ?>";
            });
            $bladeCompiler->directive('endunlessrole', function () {
                return '<?php endif; ?>';
            });
        });
    }

    /**
     * Registers micro helpers.
     *
     * @return void
     */
    protected function registerMacroHelpers()
    {
        Route::macro('role', function ($roles = []) {
            if (! is_array($roles)) {
                $roles = [$roles];
            }

            $roles = implode('|', $roles);

            $this->middleware("role:$roles");

            return $this;
        });

        Route::macro('permission', function ($permissions = []) {
            if (! is_array($permissions)) {
                $permissions = [$permissions];
            }

            $permissions = implode('|', $permissions);

            $this->middleware("permission:$permissions");

            return $this;
        });

        Collection::macro('larakeyPluckMultiple', function (array $values = []) {
            $results = [];

            $this->each(function ($item, $key) use ($values, &$results) {
                array_push($results, array_values(collect($item)->only($values)->toArray()));
            });

            if (count($results) === 1) {
                $results = $results[0];
            }

            return collect($results);
        });

        Collection::macro('larakeyMapInto', function ($type) {
            switch ($type) {
                case 'array':
                    return $this->map(function ($item) {
                        if (! is_array($item)) {
                            return [$item];
                        }
            
                        return $item;
                    });
                break;
                default:
                    throw Exception('Unknown type argument');
            }
        });

        Collection::macro('larakeyAllAre', function ($type) {
            switch ($type) {
                case 'arrays':
                    return $this->every(function ($value) {
                        return is_array($value);
                    });
                break;
                case 'strings':
                    return $this->every(function ($value) {
                        return is_string($value);
                    });
                break;
                default:
                    throw Exception('Unknown type argument');
            }
        });
    }

    /**
     * Returns existing migration file if found, else uses the current timestamp.
     *
     * @param Filesystem $filesystem
     * @return string
     */
    protected function getMigrationFileName(Filesystem $filesystem): string
    {
        $timestamp = date('Y_m_d_His');

        return Collection::make($this->app->databasePath().DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR)
            ->flatMap(function ($path) use ($filesystem) {
                return $filesystem->glob($path.'*_create_larakey_permission_tables.php');
            })->push($this->app->databasePath()."/migrations/{$timestamp}_create_larakey_permission_tables.php")
            ->first();
    }
}

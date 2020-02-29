<?php

namespace Ghustavh97\Larakey;

use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;
use Ghustavh97\Larakey\Contracts\Role as RoleContract;
use Ghustavh97\Larakey\Contracts\Permission as PermissionContract;

use Ghustavh97\Larakey\Padlock\Access as LarakeyAccess;
use Ghustavh97\Larakey\Padlock\Cache as LarakeyCache;
use Ghustavh97\Larakey\Padlock\Gate as LarakeyGate;

class LarakeyServiceProvider extends ServiceProvider
{
    public function boot(LarakeyGate $larakeyGate, LarakeyCache $larakeyCache, Filesystem $filesystem)
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

        $this->app->singleton(LarakeyCache::class, function ($app) use ($larakeyCache) {
            return $larakeyCache;
        });

        $this->app->singleton(LarakeyAccess::class, function ($app, $parameters) {
            return new LarakeyAccess($parameters['to']);
        });

        $this->app->singleton(LarakeyGate::class, function ($app) use ($larakeyGate) {
            return $larakeyGate;
        });

        $this->registerModelBindings();

        $larakeyGate->registerPermissions();

        // $permissionLoader->registerPermissions();

        // $this->app->singleton(LarakeyRegistrar::class, function ($app) use ($permissionLoader) {
        //     return $permissionLoader;
        // });

        // $this->app->singleton(LarakeyRegistrar::class, function ($app) use ($permissionLoader) {
        //     return $permissionLoader;
        // });
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/larakey.php',
            'larakey'
        );

        $this->registerBladeExtensions();
    }

    protected function registerModelBindings()
    {
        $config = $this->app->config['larakey.models'];

        $this->app->bind(PermissionContract::class, $config['permission']);
        $this->app->bind(RoleContract::class, $config['role']);
    }

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

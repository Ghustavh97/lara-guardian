<?php

namespace Ghustavh97\Larakey\Test;

use Ghustavh97\Larakey\Models\Permission;

class MultipleGuardsTest extends TestCase
{
    /** @test */
    public function it_can_give_a_permission_to_a_model_that_is_used_by_multiple_guards()
    {
        $doThis = Permission::create([
            'name' => 'do_this',
            'guard_name' => 'web',
        ]);

        $doThat = Permission::create([
            'name' => 'do_that',
            'guard_name' => 'api',
        ]);
        
        $this->testUser->givePermissionTo($doThis);

        $this->testUser->givePermissionTo($doThat);

        $this->assertTrue($this->testUser->hasPermissionTo('do_that', 'api'));
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('auth.guards', [
            'web' => ['driver' => 'session', 'provider' => 'users'],
            'api' => ['driver' => 'jwt', 'provider' => 'users'],
            'abc' => ['driver' => 'abc'],
        ]);
    }
}

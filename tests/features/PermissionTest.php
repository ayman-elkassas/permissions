<?php

use App\User;
use Faker\Factory;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use LaravelEnso\PermissionManager\app\Models\Permission;
use LaravelEnso\PermissionManager\app\Models\PermissionGroup;
use LaravelEnso\RoleManager\app\Models\Role;
use LaravelEnso\TestHelper\app\Classes\TestHelper;
use LaravelEnso\TestHelper\app\Traits\TestCreateForm;
use LaravelEnso\TestHelper\app\Traits\TestDataTable;

class PermissionTest extends TestHelper
{
    use DatabaseMigrations, TestDataTable, TestCreateForm;

    private $faker;
    private $prefix = 'system.permissions';

    protected function setUp()
    {
        parent::setUp();

        $this->disableExceptionHandling();
        $this->faker = Factory::create();
        $this->signIn(User::first());
    }

    /** @test */
    public function store()
    {
        $postParams = $this->postParams();
        $response = $this->post(route('system.permissions.store', [], false), $postParams);

        $permission = Permission::whereName($postParams['name'])->first();

        $response->assertStatus(200)
            ->assertJsonFragment([
                'message'  => 'The permission was created!',
                'redirect' => '/system/permissions/'.$permission->id.'/edit',
            ]);
    }

    /** @test */
    public function edit()
    {
        $permission = Permission::create($this->postParams());

        $this->get(route('system.permissions.edit', $permission->id, false))
            ->assertStatus(200)
            ->assertJsonStructure(['form']);
    }

    /** @test */
    public function update()
    {
        $permission = Permission::create($this->postParams());
        $permission->description = 'edited';

        $this->patch(route('system.permissions.update', $permission->id, false), $permission->toArray())
            ->assertStatus(200)
            ->assertJson(['message' => __(config('enso.labels.savedChanges'))]);

        $this->assertEquals('edited', $permission->fresh()->description);
    }

    /** @test */
    public function destroy()
    {
        $permission = Permission::create($this->postParams());

        $this->delete(route('system.permissions.destroy', $permission->id, false))
            ->assertStatus(200)
            ->assertJsonStructure(['message']);

        $this->assertNull($permission->fresh());
    }

    /** @test */
    public function cant_destroy_if_has_roles()
    {
        $permission = Permission::create($this->postParams());
        $role = Role::first(['id']);
        $permission->roles()->attach($role->id);

        $this->expectException(EnsoException::class);

        $this->delete(route('system.permissions.destroy', $permission->id, false))
            ->assertStatus(302)
            ->assertJsonStructure(['message']);

        $this->assertNotNull($permission->fresh());
    }

    private function postParams()
    {
        return [
            'permission_group_id' => PermissionGroup::first(['id'])->id,
            'name'                => $this->faker->word,
            'description'         => $this->faker->sentence,
            'type'                => 0,
            'default'             => 0,
            '_method'             => 'POST',
        ];
    }
}
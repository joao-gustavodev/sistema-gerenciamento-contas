<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_administrators_can_open_user_and_permissions_tabs(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_VIEWER]));
        $this->get(route('users.index'))->assertForbidden();
        $this->get(route('permissions.index'))->assertForbidden();

        $this->actingAs(User::factory()->create(['role' => User::ROLE_EDITOR]));
        $this->get(route('users.index'))->assertForbidden();
        $this->get(route('permissions.index'))->assertForbidden();

        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));
        $this->get(route('users.index'))->assertOk();
        $this->get(route('permissions.index'))->assertOk();
    }

    public function test_administrator_can_create_and_assign_accounts_access_roles(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));

        $this->post(route('users.store'), [
            'name' => 'Editora',
            'email' => 'editor@example.test',
            'role' => User::ROLE_EDITOR,
            'password' => 'secure-password-123',
            'password_confirmation' => 'secure-password-123',
        ])->assertRedirect(route('users.index'));

        $editor = User::query()->where('email', 'editor@example.test')->firstOrFail();
        $this->assertSame(User::ROLE_EDITOR, $editor->role);
        $this->assertTrue(Hash::check('secure-password-123', $editor->password));

        $this->put(route('users.update', $editor), [
            'name' => 'Visualizadora',
            'email' => $editor->email,
            'role' => User::ROLE_VIEWER,
            'password' => '',
            'password_confirmation' => '',
        ])->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $editor->id,
            'name' => 'Visualizadora',
            'role' => User::ROLE_VIEWER,
        ]);
    }

    public function test_administrator_cannot_remove_the_last_admin_or_change_own_role(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin);

        $this->put(route('users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => User::ROLE_VIEWER,
        ])->assertSessionHasErrors('role');

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'role' => User::ROLE_ADMIN,
        ]);
    }

    public function test_administrator_cannot_delete_self_or_users_with_financial_records(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $editor = User::factory()->create(['role' => User::ROLE_EDITOR]);
        $this->actingAs($admin);

        $this->delete(route('users.destroy', $admin))->assertSessionHasErrors('user');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);

        $editor->accounts()->create([
            'name' => 'Conta vinculada',
            'bank_name' => 'Banco Exemplo',
            'account_number' => '121212',
            'type' => 'checking',
            'currency' => 'BRL',
            'opening_balance_minor' => 0,
            'is_active' => true,
        ]);

        $this->delete(route('users.destroy', $editor))->assertSessionHasErrors('user');
        $this->assertDatabaseHas('users', ['id' => $editor->id]);
    }
}

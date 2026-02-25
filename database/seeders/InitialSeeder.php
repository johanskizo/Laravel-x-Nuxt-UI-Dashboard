<?php

namespace Database\Seeders;

use App\Models\Profile;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class InitialSeeder extends Seeder
{
	public function run()
	{
		$permissions = [
			'Dashboard.view',
			'User.view',
			'User.edit',
			'User.delete',
			'Privilege.role.view',
			'Privilege.role.add',
			'Privilege.role.edit',
			'Privilege.role.delete',
			'Privilege.role.user.view',
			'Privilege.role.user.add',
			'Privilege.role.user.delete',
			'Privilege.role.permission.view',
			'Privilege.role.permission.edit',
			'Privilege.permission.view',
			'Privilege.permission.add',
			'Privilege.permission.edit',
			'Privilege.permission.delete'
		];

		foreach ($permissions as $row) {
			Permission::Create(['name' => $row, 'guard_name' => 'api']);
		}

		$role = Role::create(['name' => 'System Administrator', 'guard_name' => 'api']);

		$role->syncPermissions($permissions);

		$user = User::create([
			'name' => 'administrator',
			'email' => 'administrator@mail.com',
			'password' => Hash::make('12345678'),
			'is_active' => 1,
			'email_verified_at' => now()
		]);

		$user->assignRole($role);

		Profile::create([
			'user_id' => $user->id,
			'identity_number' => '0000000000000000',
			'full_name' => 'John Doe',
			'birth_date' => '1945-17-07',
			'gender' => 'male',
			'phone_number' => '081234567890',
			'address' => 'localhost',
			'created_by' => $user->id
		]);
	}
}

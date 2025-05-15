<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'employee']);

        // Create permissions
        Permission::create(['name' => 'manage leaves']);
        Permission::create(['name' => 'create leave']);
        Permission::create(['name' => 'view leaves']);

        // Assign permissions to roles
        $adminRole = Role::findByName('admin');
        $adminRole->givePermissionTo(['manage leaves', 'view leaves']);

        $employeeRole = Role::findByName('employee');
        $employeeRole->givePermissionTo(['create leave', 'view leaves']);
    }
}
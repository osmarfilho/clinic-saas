<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'view dashboard',
            'view patients',
            'manage patients',
            'manage appointments',
            'access finance',
            'view reports',
            'manage clinic settings',
            'view notifications',
            'register encounters',
            'view audit logs',
            'manage users',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $roles = [
            'Super Admin' => $permissions,
            'Admin da Clínica' => $permissions,
            'Médico' => [
                'view dashboard',
                'view patients',
                'manage appointments',
                'register encounters',
                'view notifications',
            ],
            'Recepcionista' => [
                'view dashboard',
                'view patients',
                'manage patients',
                'manage appointments',
                'view notifications',
            ],
            'Financeiro' => [
                'view dashboard',
                'access finance',
                'view reports',
                'view notifications',
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            Role::firstOrCreate(['name' => $roleName,'guard_name' => 'web',])->syncPermissions($rolePermissions);
        }

        foreach (['admin', 'medico', 'recepcionista'] as $legacyRole) {
            Role::firstOrCreate(['name' => $legacyRole,'guard_name' => 'web',]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}

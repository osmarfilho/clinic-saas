<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::firstOrCreate([
            'name' => 'manage clinics',
            'guard_name' => 'web',
        ]);

        $superAdminRole = Role::firstOrCreate([
            'name' => 'Super Admin',
            'guard_name' => 'web',
        ]);

        $superAdminRole->givePermissionTo($permission);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::where([
            'name' => 'manage clinics',
            'guard_name' => 'web',
        ])->first();

        if ($permission) {
            $superAdminRole = Role::where([
                'name' => 'Super Admin',
                'guard_name' => 'web',
            ])->first();

            if ($superAdminRole) {
                $superAdminRole->revokePermissionTo($permission);
            }

            $permission->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};

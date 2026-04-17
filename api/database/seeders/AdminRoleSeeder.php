<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminRoleSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'finance:read',
            'payouts:process',
            'bank_transfer:confirm',
            'cash_invoices:manage',
            'kyc:review',
            'staff:manage',
            'support:manage',
            'disputes:manage',
            'feature_flags:manage',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $roles = [
            'super_admin' => $permissions,
            'finance_admin' => ['finance:read', 'payouts:process', 'bank_transfer:confirm', 'cash_invoices:manage'],
            'marketing_admin' => ['feature_flags:manage'],
            'hr_admin' => ['staff:manage'],
            'customer_service_agent' => ['support:manage'],
            'kyc_officer' => ['kyc:review'],
            'issue_resolver' => ['disputes:manage'],
        ];

        foreach ($roles as $roleName => $perms) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions($perms);
        }
    }
}

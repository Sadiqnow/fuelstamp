<?php

namespace Database\Seeders;

use App\Enums\RoleCode;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::firstOrCreate(
            [
                'tenant_code' => 'TEN-DEMO-001',
            ],
            [
                'name' => 'Northern Fuels Limited',
                'status' => 'ACTIVE',
            ]
        );

        $users = [
            [
                'user_code' => 'OWN-001',
                'name' => 'Station Owner Demo',
                'phone' => '08000000001',
                'email' => 'owner@fuelstamp.local',
                'role' => RoleCode::STATION_OWNER,
            ],
            [
                'user_code' => 'MGR-001',
                'name' => 'Station Manager Demo',
                'phone' => '08000000002',
                'email' => 'manager@fuelstamp.local',
                'role' => RoleCode::STATION_MANAGER,
            ],
            [
                'user_code' => 'ATD-821',
                'name' => 'Bello Sani',
                'phone' => '08000000003',
                'email' => 'attendant@fuelstamp.local',
                'role' => RoleCode::ATTENDANT,
            ],
            [
                'user_code' => 'BYR-100482',
                'name' => 'Buyer Demo',
                'phone' => '08000000004',
                'email' => 'buyer@fuelstamp.local',
                'role' => RoleCode::BUYER,
            ],
        ];

        foreach ($users as $demo) {

            $user = User::updateOrCreate(
                [
                    'user_code' => $demo['user_code'],
                ],
                [
                    'tenant_id' => $tenant->id,
                    'name' => $demo['name'],
                    'phone' => $demo['phone'],
                    'email' => $demo['email'],
                    'password' => Hash::make('Fuelstamp123!'),
                    'status' => 'ACTIVE',
                ]
            );

            $role = Role::where(
                'code',
                $demo['role']->value
            )->firstOrFail();

            UserRole::firstOrCreate([
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'role_id' => $role->id,
                'station_id' => null,
                'effective_from' => now(),
            ]);
        }

        /*
         * PLATFORM_ADMIN deliberately has no station tenant dependency.
         */
        $admin = User::updateOrCreate(
            [
                'user_code' => 'ADM-0042',
            ],
            [
                'tenant_id' => null,
                'name' => 'Platform Admin Demo',
                'phone' => '08000000005',
                'email' => 'admin@fuelstamp.local',
                'password' => Hash::make('Fuelstamp123!'),
                'status' => 'ACTIVE',
            ]
        );

        $adminRole = Role::where(
            'code',
            RoleCode::PLATFORM_ADMIN->value
        )->firstOrFail();

        UserRole::firstOrCreate([
            'tenant_id' => null,
            'user_id' => $admin->id,
            'role_id' => $adminRole->id,
            'station_id' => null,
            'effective_from' => now(),
        ]);
    }
}
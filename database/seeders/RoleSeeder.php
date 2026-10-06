<?php

namespace Database\Seeders;

use App\Enums\RoleCode;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            RoleCode::BUYER->value => 'Fuel Buyer',
            RoleCode::ATTENDANT->value => 'Fuel Attendant',
            RoleCode::STATION_MANAGER->value => 'Station Manager',
            RoleCode::STATION_OWNER->value => 'Station Owner',
            RoleCode::PLATFORM_ADMIN->value => 'Platform Admin',
        ];

        foreach ($roles as $code => $name) {
            Role::updateOrCreate(
                ['code' => $code],
                ['name' => $name]
            );
        }
    }
}
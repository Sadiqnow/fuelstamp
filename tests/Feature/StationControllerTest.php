<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Station;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_station_owner_can_manage_only_stations_in_their_tenant(): void
    {
        $tenant = Tenant::create([
            'tenant_code' => 'TEN-'.Str::upper(Str::random(8)),
            'name' => 'Owner Tenant',
            'status' => 'ACTIVE',
        ]);
        $otherTenant = Tenant::create([
            'tenant_code' => 'TEN-'.Str::upper(Str::random(8)),
            'name' => 'Other Tenant',
            'status' => 'ACTIVE',
        ]);

        $owner = $this->userWithRole('STATION_OWNER', $tenant);
        Sanctum::actingAs($owner);

        $otherStation = Station::create([
            'tenant_id' => $otherTenant->id,
            'station_code' => 'OTHER-001',
            'name' => 'Other Station',
            'address' => 'Other Address',
        ]);

        $this->getJson('/api/v1/stations')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->getJson('/api/v1/stations/'.$otherStation->id)
            ->assertNotFound();

        $this->postJson('/api/v1/stations', [
            'station_code' => 'OWNER-001',
            'name' => 'Owner Station',
            'address' => 'Owner Address',
            'tenant_id' => $otherTenant->id,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('tenant_id');

        $created = $this->postJson('/api/v1/stations', [
            'station_code' => 'OWNER-001',
            'name' => 'Owner Station',
            'address' => 'Owner Address',
        ])->assertCreated()
            ->assertJsonPath('data.station_code', 'OWNER-001');

        $stationId = $created->json('data.id');
        $this->assertDatabaseHas('stations', [
            'id' => $stationId,
            'tenant_id' => $tenant->id,
        ]);

        $this->getJson('/api/v1/stations')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson('/api/v1/stations/'.$stationId)
            ->assertOk()
            ->assertJsonPath('data.id', $stationId);

        $this->patchJson('/api/v1/stations/'.$stationId, [
            'name' => 'Updated Owner Station',
        ])->assertOk()
            ->assertJsonPath('data.name', 'Updated Owner Station');

        $this->deleteJson('/api/v1/stations/'.$stationId)
            ->assertNoContent();
    }

    public function test_platform_admin_can_create_a_station_for_a_selected_tenant(): void
    {
        $tenant = Tenant::create([
            'tenant_code' => 'TEN-'.Str::upper(Str::random(8)),
            'name' => 'Admin Tenant',
            'status' => 'ACTIVE',
        ]);

        Sanctum::actingAs($this->userWithRole('PLATFORM_ADMIN'));

        $this->postJson('/api/v1/stations', [
            'tenant_id' => $tenant->id,
            'station_code' => 'ADMIN-001',
            'name' => 'Admin Station',
            'address' => 'Admin Address',
        ])->assertCreated();

        $this->assertDatabaseHas('stations', [
            'tenant_id' => $tenant->id,
            'station_code' => 'ADMIN-001',
        ]);
    }

    private function userWithRole(string $roleCode, ?Tenant $tenant = null): User
    {
        $role = Role::create([
            'code' => $roleCode,
            'name' => $roleCode,
        ]);
        $user = User::create([
            'tenant_id' => $tenant?->id,
            'user_code' => 'USR-'.Str::upper(Str::random(8)),
            'name' => 'Test User',
            'phone' => '07'.random_int(100000000, 999999999),
            'email' => Str::uuid().'@example.test',
            'password' => 'password',
            'status' => 'ACTIVE',
        ]);

        UserRole::create([
            'tenant_id' => $tenant?->id,
            'user_id' => $user->id,
            'role_id' => $role->id,
            'effective_from' => now(),
        ]);

        return $user;
    }
}

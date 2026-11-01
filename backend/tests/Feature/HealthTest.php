<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HealthTest extends TestCase
{
    public function test_health_endpoint_returns_200_when_dependencies_are_available(): void
    {
        $this->getJson('/health')
            ->assertOk()
            ->assertJsonPath('status', 'healthy')
            ->assertJsonPath('checks.database.status', 'up')
            ->assertJsonPath('checks.cache.status', 'up');
    }

    public function test_health_endpoint_returns_503_when_database_is_unavailable(): void
    {
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.host' => 'health-db.invalid',
            'database.connections.mysql.port' => 3306,
        ]);
        DB::purge('mysql');

        $this->getJson('/health')
            ->assertStatus(503)
            ->assertJsonPath('status', 'unhealthy')
            ->assertJsonPath('checks.database.status', 'down')
            ->assertJsonPath('checks.cache.status', 'down');
    }
}

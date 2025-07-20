<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Contracts\Console\Kernel;
use Tests\Traits\RoleSetupTrait;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase, RoleSetupTrait;

    /**
     * Indicates if the test is using in-memory database
     */
    protected $seed = false;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure we're using in-memory database for tests
        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);
        
        // Clear any existing permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        
        // Setup roles for all tests
        $this->setupRoles();
    }

    /**
     * Create application.
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        
        return $app;
    }

    /**
     * Refresh the in-memory database.
     */
    protected function refreshInMemoryDatabase()
    {
        $this->artisan('migrate:fresh', [
            '--drop-views' => true,
            '--drop-types' => true,
            '--quiet' => true,
        ]);

        $this->app[Kernel::class]->setArtisan(null);
    }
}

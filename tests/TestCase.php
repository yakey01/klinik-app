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
        
        // Create unique SQLite database file for each test process to avoid conflicts
        $this->setupUniqueTestDatabase();
        
        // Clear any existing permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        
        // Setup roles for all tests
        $this->setupRoles();
    }
    
    /**
     * Setup unique SQLite database for each test process
     */
    protected function setupUniqueTestDatabase(): void
    {
        // Use consistent database file name that matches phpunit.xml configuration
        $databasePath = "database/testing.sqlite";
        
        // Ensure database directory exists
        if (!is_dir(dirname($databasePath))) {
            mkdir(dirname($databasePath), 0755, true);
        }
        
        // Create empty database file if it doesn't exist
        if (!file_exists($databasePath)) {
            touch($databasePath);
        }
        
        // Configure database connection to use the same file as phpunit.xml
        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => $databasePath]);
        
        // Force a fresh database connection
        \DB::purge('sqlite');
        \DB::reconnect('sqlite');
        
        // Run migrations fresh for this database
        $this->artisan('migrate:fresh', [
            '--drop-views' => true,
            '--quiet' => true,
        ]);
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
     * Refresh the test database.
     */
    protected function refreshTestDatabase()
    {
        $this->artisan('migrate:fresh', [
            '--drop-views' => true,
            '--quiet' => true,
        ]);

        $this->app[Kernel::class]->setArtisan(null);
    }
    
    /**
     * Clean up test database files after tests
     */
    protected function tearDown(): void
    {
        // Clean up test database files
        $this->cleanupTestDatabases();
        
        parent::tearDown();
    }
    
    /**
     * Clean up test database files
     */
    protected function cleanupTestDatabases(): void
    {
        $databaseDir = 'database';
        if (is_dir($databaseDir)) {
            $files = glob($databaseDir . '/testing*.sqlite');
            foreach ($files as $file) {
                // Only delete files older than 1 hour to avoid conflicts with running tests
                if (filemtime($file) < (time() - 3600)) {
                    unlink($file);
                }
            }
        }
    }
}

<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use App\Services\PetugasConfigService;

class PetugasConfigServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PetugasConfigService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new PetugasConfigService();
    }

    public function test_it_gets_navigation_groups_configuration()
    {
        // Act
        $navigationGroups = $this->service->getNavigationGroups();
        
        // Assert
        $this->assertIsArray($navigationGroups);
        $this->assertArrayHasKey('dashboard', $navigationGroups);
        $this->assertArrayHasKey('data_entry', $navigationGroups);
        $this->assertArrayHasKey('financial', $navigationGroups);
        $this->assertArrayHasKey('patient_care', $navigationGroups);
        
        // Check structure of navigation group
        $dashboardGroup = $navigationGroups['dashboard'];
        $this->assertArrayHasKey('label', $dashboardGroup);
        $this->assertArrayHasKey('icon', $dashboardGroup);
        $this->assertArrayHasKey('collapsible', $dashboardGroup);
        $this->assertArrayHasKey('sort', $dashboardGroup);
        
        $this->assertEquals('🏠 Dashboard', $dashboardGroup['label']);
        $this->assertEquals('heroicon-o-home', $dashboardGroup['icon']);
        $this->assertTrue($dashboardGroup['collapsible']);
        $this->assertEquals(1, $dashboardGroup['sort']);
    }

    public function test_it_gets_form_fields_configuration()
    {
        // Act
        $formFields = $this->service->getFormFields();
        
        // Assert
        $this->assertIsArray($formFields);
        $this->assertArrayHasKey('gender_options', $formFields);
        $this->assertArrayHasKey('shift_options', $formFields);
        $this->assertArrayHasKey('status_options', $formFields);
        $this->assertArrayHasKey('validation_status_options', $formFields);
        $this->assertArrayHasKey('priority_options', $formFields);
        
        // Check gender options
        $genderOptions = $formFields['gender_options'];
        $this->assertArrayHasKey('L', $genderOptions);
        $this->assertArrayHasKey('P', $genderOptions);
        $this->assertEquals('Laki-laki', $genderOptions['L']);
        $this->assertEquals('Perempuan', $genderOptions['P']);
        
        // Check shift options
        $shiftOptions = $formFields['shift_options'];
        $this->assertArrayHasKey('Pagi', $shiftOptions);
        $this->assertArrayHasKey('Sore', $shiftOptions);
        $this->assertArrayHasKey('Malam', $shiftOptions);
        
        // Check validation status options
        $validationOptions = $formFields['validation_status_options'];
        $this->assertArrayHasKey('pending', $validationOptions);
        $this->assertArrayHasKey('approved', $validationOptions);
        $this->assertArrayHasKey('rejected', $validationOptions);
        $this->assertArrayHasKey('revision', $validationOptions);
    }

    public function test_it_gets_resource_configurations()
    {
        // Act
        $resourceConfigs = $this->service->getResourceConfigs();
        
        // Assert
        $this->assertIsArray($resourceConfigs);
        $this->assertArrayHasKey('pasien', $resourceConfigs);
        $this->assertArrayHasKey('pendapatan_harian', $resourceConfigs);
        $this->assertArrayHasKey('pengeluaran_harian', $resourceConfigs);
        $this->assertArrayHasKey('tindakan', $resourceConfigs);
        $this->assertArrayHasKey('jumlah_pasien_harian', $resourceConfigs);
        
        // Check pasien resource config
        $pasienConfig = $resourceConfigs['pasien'];
        $this->assertArrayHasKey('navigation_label', $pasienConfig);
        $this->assertArrayHasKey('model_label', $pasienConfig);
        $this->assertArrayHasKey('navigation_icon', $pasienConfig);
        $this->assertArrayHasKey('navigation_group', $pasienConfig);
        $this->assertArrayHasKey('navigation_sort', $pasienConfig);
        $this->assertArrayHasKey('table_heading', $pasienConfig);
        $this->assertArrayHasKey('table_description', $pasienConfig);
        $this->assertArrayHasKey('record_prefix', $pasienConfig);
        $this->assertArrayHasKey('record_format', $pasienConfig);
        
        $this->assertEquals('👤 Pasien', $pasienConfig['navigation_label']);
        $this->assertEquals('heroicon-o-user-plus', $pasienConfig['navigation_icon']);
        $this->assertEquals('patient_management', $pasienConfig['navigation_group']);
        $this->assertEquals('RM', $pasienConfig['record_prefix']);
        $this->assertEquals('RM-{year}-{sequence}', $pasienConfig['record_format']);
    }

    public function test_it_gets_validation_configuration()
    {
        // Act
        $validationConfig = $this->service->getValidationConfig();
        
        // Assert
        $this->assertIsArray($validationConfig);
        $this->assertArrayHasKey('auto_approval_thresholds', $validationConfig);
        $this->assertArrayHasKey('validation_required_fields', $validationConfig);
        $this->assertArrayHasKey('approval_levels', $validationConfig);
        
        // Check auto approval thresholds
        $thresholds = $validationConfig['auto_approval_thresholds'];
        $this->assertArrayHasKey('tindakan', $thresholds);
        $this->assertArrayHasKey('pendapatan_harian', $thresholds);
        $this->assertArrayHasKey('pengeluaran_harian', $thresholds);
        
        $this->assertEquals(100000, $thresholds['tindakan']);
        $this->assertEquals(500000, $thresholds['pendapatan_harian']);
        $this->assertEquals(200000, $thresholds['pengeluaran_harian']);
        
        // Check validation required fields
        $requiredFields = $validationConfig['validation_required_fields'];
        $this->assertArrayHasKey('tindakan', $requiredFields);
        $this->assertContains('jenis_tindakan_id', $requiredFields['tindakan']);
        $this->assertContains('pasien_id', $requiredFields['tindakan']);
        $this->assertContains('tanggal_tindakan', $requiredFields['tindakan']);
        $this->assertContains('tarif', $requiredFields['tindakan']);
        
        // Check approval levels
        $approvalLevels = $validationConfig['approval_levels'];
        $this->assertArrayHasKey('tindakan', $approvalLevels);
        $this->assertContains('supervisor', $approvalLevels['tindakan']);
        $this->assertContains('manager', $approvalLevels['tindakan']);
    }

    public function test_it_gets_ui_configuration()
    {
        // Act
        $uiConfig = $this->service->getUIConfig();
        
        // Assert
        $this->assertIsArray($uiConfig);
        $this->assertArrayHasKey('colors', $uiConfig);
        $this->assertArrayHasKey('icons', $uiConfig);
        $this->assertArrayHasKey('pagination', $uiConfig);
        $this->assertArrayHasKey('date_formats', $uiConfig);
        
        // Check colors
        $colors = $uiConfig['colors'];
        $this->assertArrayHasKey('primary', $colors);
        $this->assertArrayHasKey('success', $colors);
        $this->assertArrayHasKey('warning', $colors);
        $this->assertArrayHasKey('danger', $colors);
        
        $this->assertEquals('rgb(102, 126, 234)', $colors['primary']);
        $this->assertEquals('rgb(16, 185, 129)', $colors['success']);
        
        // Check icons
        $icons = $uiConfig['icons'];
        $this->assertArrayHasKey('loading', $icons);
        $this->assertArrayHasKey('success', $icons);
        $this->assertArrayHasKey('error', $icons);
        $this->assertArrayHasKey('warning', $icons);
        
        // Check pagination
        $pagination = $uiConfig['pagination'];
        $this->assertArrayHasKey('per_page_options', $pagination);
        $this->assertArrayHasKey('default_per_page', $pagination);
        $this->assertEquals([10, 25, 50, 100], $pagination['per_page_options']);
        $this->assertEquals(25, $pagination['default_per_page']);
        
        // Check date formats
        $dateFormats = $uiConfig['date_formats'];
        $this->assertArrayHasKey('display', $dateFormats);
        $this->assertArrayHasKey('display_with_time', $dateFormats);
        $this->assertArrayHasKey('input', $dateFormats);
        $this->assertArrayHasKey('input_with_time', $dateFormats);
        
        $this->assertEquals('d/m/Y', $dateFormats['display']);
        $this->assertEquals('d/m/Y H:i', $dateFormats['display_with_time']);
        $this->assertEquals('Y-m-d', $dateFormats['input']);
        $this->assertEquals('Y-m-d H:i:s', $dateFormats['input_with_time']);
    }

    public function test_it_gets_config_by_key()
    {
        // Act
        $navigationGroups = $this->service->getConfig('navigation_groups');
        $formFields = $this->service->getConfig('form_fields');
        $nonExistent = $this->service->getConfig('non_existent_key');
        $withDefault = $this->service->getConfig('non_existent_key', 'default_value');
        
        // Assert
        $this->assertIsArray($navigationGroups);
        $this->assertArrayHasKey('dashboard', $navigationGroups);
        
        $this->assertIsArray($formFields);
        $this->assertArrayHasKey('gender_options', $formFields);
        
        $this->assertNull($nonExistent);
        $this->assertEquals('default_value', $withDefault);
    }

    public function test_it_caches_configurations()
    {
        // Arrange
        Cache::flush();
        
        // Act - First call should cache the data
        $firstCall = $this->service->getNavigationGroups();
        
        // Check if cache exists
        $this->assertTrue(Cache::has('petugas_navigation_groups'));
        
        // Second call should use cache
        $secondCall = $this->service->getNavigationGroups();
        
        // Assert
        $this->assertEquals($firstCall, $secondCall);
        $this->assertArrayHasKey('dashboard', $firstCall);
        $this->assertArrayHasKey('dashboard', $secondCall);
    }

    public function test_it_clears_cache_successfully()
    {
        // Arrange
        Cache::flush();
        
        // Cache some data
        $this->service->getNavigationGroups();
        $this->service->getFormFields();
        $this->service->getResourceConfigs();
        
        // Verify cache exists
        $this->assertTrue(Cache::has('petugas_navigation_groups'));
        $this->assertTrue(Cache::has('petugas_form_fields'));
        $this->assertTrue(Cache::has('petugas_resource_configs'));
        
        // Act
        $result = $this->service->clearCache();
        
        // Assert
        $this->assertTrue($result);
        $this->assertFalse(Cache::has('petugas_navigation_groups'));
        $this->assertFalse(Cache::has('petugas_form_fields'));
        $this->assertFalse(Cache::has('petugas_resource_configs'));
    }

    public function test_it_handles_cache_failures_gracefully()
    {
        // Arrange - Mock cache failure
        Cache::shouldReceive('remember')
            ->andThrow(new \Exception('Cache failure'));
        Cache::shouldReceive('flush')
            ->andReturn(true);
        
        // Act
        $navigationGroups = $this->service->getNavigationGroups();
        
        // Assert - Should fall back to default configuration
        $this->assertIsArray($navigationGroups);
        $this->assertArrayHasKey('dashboard', $navigationGroups);
        $this->assertEquals('🏠 Dashboard', $navigationGroups['dashboard']['label']);
    }

    public function test_it_provides_fallback_configurations()
    {
        // Test that fallback methods exist and work
        $service = new class extends PetugasConfigService {
            public function testGetDefaultNavigationGroups()
            {
                return $this->getDefaultNavigationGroups();
            }
            
            public function testGetDefaultFormFields()
            {
                return $this->getDefaultFormFields();
            }
            
            public function testGetDefaultResourceConfigs()
            {
                return $this->getDefaultResourceConfigs();
            }
            
            public function testGetDefaultValidationConfig()
            {
                return $this->getDefaultValidationConfig();
            }
            
            public function testGetDefaultUIConfig()
            {
                return $this->getDefaultUIConfig();
            }
        };
        
        // Act
        $defaultNavigation = $service->testGetDefaultNavigationGroups();
        $defaultFormFields = $service->testGetDefaultFormFields();
        $defaultResourceConfigs = $service->testGetDefaultResourceConfigs();
        $defaultValidationConfig = $service->testGetDefaultValidationConfig();
        $defaultUIConfig = $service->testGetDefaultUIConfig();
        
        // Assert
        $this->assertIsArray($defaultNavigation);
        $this->assertArrayHasKey('dashboard', $defaultNavigation);
        
        $this->assertIsArray($defaultFormFields);
        $this->assertArrayHasKey('gender_options', $defaultFormFields);
        
        $this->assertIsArray($defaultResourceConfigs);
        $this->assertArrayHasKey('pasien', $defaultResourceConfigs);
        
        $this->assertIsArray($defaultValidationConfig);
        $this->assertArrayHasKey('auto_approval_thresholds', $defaultValidationConfig);
        
        $this->assertIsArray($defaultUIConfig);
        $this->assertArrayHasKey('colors', $defaultUIConfig);
    }

    public function test_it_validates_configuration_structure()
    {
        // Test that all required configuration keys exist
        $navigationGroups = $this->service->getNavigationGroups();
        $formFields = $this->service->getFormFields();
        $resourceConfigs = $this->service->getResourceConfigs();
        $validationConfig = $this->service->getValidationConfig();
        $uiConfig = $this->service->getUIConfig();
        
        // Assert all configurations are properly structured
        $this->assertConfigurationStructure($navigationGroups, [
            'dashboard', 'data_entry', 'financial', 'patient_care'
        ]);
        
        $this->assertConfigurationStructure($formFields, [
            'gender_options', 'shift_options', 'status_options', 'validation_status_options', 'priority_options'
        ]);
        
        $this->assertConfigurationStructure($resourceConfigs, [
            'pasien', 'pendapatan_harian', 'pengeluaran_harian', 'tindakan', 'jumlah_pasien_harian'
        ]);
        
        $this->assertConfigurationStructure($validationConfig, [
            'auto_approval_thresholds', 'validation_required_fields', 'approval_levels'
        ]);
        
        $this->assertConfigurationStructure($uiConfig, [
            'colors', 'icons', 'pagination', 'date_formats'
        ]);
    }

    private function assertConfigurationStructure(array $config, array $requiredKeys)
    {
        $this->assertIsArray($config);
        
        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey($key, $config, "Configuration missing required key: {$key}");
        }
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        Cache::flush();
        parent::tearDown();
    }
}
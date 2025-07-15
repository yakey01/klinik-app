<?php

namespace Tests\Unit\Widgets;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use App\Filament\Petugas\Widgets\NotificationWidget;
use App\Services\NotificationService;
use App\Services\TelegramService;
use App\Models\User;
use Mockery;

class NotificationWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected NotificationWidget $widget;
    protected User $user;
    protected NotificationService $notificationService;
    protected TelegramService $telegramService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        Auth::login($this->user);
        
        $this->telegramService = Mockery::mock(TelegramService::class);
        $this->notificationService = Mockery::mock(NotificationService::class);
        
        $this->widget = new NotificationWidget();
    }

    public function test_it_initializes_services_correctly()
    {
        // Act
        $widget = new NotificationWidget();
        
        // Assert - Widget should initialize without errors
        $this->assertInstanceOf(NotificationWidget::class, $widget);
    }

    public function test_it_gets_view_data_successfully()
    {
        // Arrange
        $mockNotifications = [
            [
                'id' => 'notif_1',
                'title' => 'Test Notification',
                'message' => 'This is a test notification',
                'priority' => 'medium',
                'type' => 'validation_pending',
                'created_at' => now()->toISOString(),
                'read_at' => null,
            ],
            [
                'id' => 'notif_2',
                'title' => 'Another Notification',
                'message' => 'This is another test notification',
                'priority' => 'high',
                'type' => 'task_reminder',
                'created_at' => now()->subHour()->toISOString(),
                'read_at' => now()->subMinutes(30)->toISOString(),
            ],
        ];

        $this->notificationService->shouldReceive('getUserNotifications')
            ->once()
            ->with($this->user->id, 10)
            ->andReturn([
                'success' => true,
                'notifications' => $mockNotifications,
                'total' => 2,
                'unread' => 1,
            ]);

        $this->app->instance(NotificationService::class, $this->notificationService);

        // Act
        $viewData = $this->widget->getViewData();

        // Assert
        $this->assertIsArray($viewData);
        $this->assertArrayHasKey('notifications', $viewData);
        $this->assertArrayHasKey('total', $viewData);
        $this->assertArrayHasKey('unread', $viewData);
        $this->assertArrayHasKey('last_updated', $viewData);
        $this->assertArrayHasKey('user_id', $viewData);

        $this->assertEquals($mockNotifications, $viewData['notifications']);
        $this->assertEquals(2, $viewData['total']);
        $this->assertEquals(1, $viewData['unread']);
        $this->assertEquals($this->user->id, $viewData['user_id']);
        $this->assertMatchesRegularExpression('/\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}/', $viewData['last_updated']);
    }

    public function test_it_handles_empty_notifications()
    {
        // Arrange
        $this->notificationService->shouldReceive('getUserNotifications')
            ->once()
            ->with($this->user->id, 10)
            ->andReturn([
                'success' => true,
                'notifications' => [],
                'total' => 0,
                'unread' => 0,
            ]);

        $this->app->instance(NotificationService::class, $this->notificationService);

        // Act
        $viewData = $this->widget->getViewData();

        // Assert
        $this->assertIsArray($viewData);
        $this->assertEmpty($viewData['notifications']);
        $this->assertEquals(0, $viewData['total']);
        $this->assertEquals(0, $viewData['unread']);
    }

    public function test_it_handles_unauthenticated_user()
    {
        // Arrange
        Auth::logout();

        // Act
        $viewData = $this->widget->getViewData();

        // Assert
        $this->assertIsArray($viewData);
        $this->assertArrayHasKey('error', $viewData);
        $this->assertEquals('Tidak ada user yang terautentikasi', $viewData['error']);
        $this->assertEmpty($viewData['notifications']);
        $this->assertEquals(0, $viewData['total']);
        $this->assertEquals(0, $viewData['unread']);
    }

    public function test_it_handles_notification_service_failure()
    {
        // Arrange
        $this->notificationService->shouldReceive('getUserNotifications')
            ->once()
            ->with($this->user->id, 10)
            ->andReturn([
                'success' => false,
                'error' => 'Service unavailable',
                'notifications' => [],
                'total' => 0,
                'unread' => 0,
            ]);

        $this->app->instance(NotificationService::class, $this->notificationService);

        // Act
        $viewData = $this->widget->getViewData();

        // Assert
        $this->assertIsArray($viewData);
        $this->assertArrayHasKey('error', $viewData);
        $this->assertEquals('Gagal memuat notifikasi', $viewData['error']);
        $this->assertEmpty($viewData['notifications']);
        $this->assertEquals(0, $viewData['total']);
        $this->assertEquals(0, $viewData['unread']);
    }

    public function test_it_handles_service_exception()
    {
        // Arrange
        $this->notificationService->shouldReceive('getUserNotifications')
            ->once()
            ->with($this->user->id, 10)
            ->andThrow(new \Exception('Service exception'));

        $this->app->instance(NotificationService::class, $this->notificationService);

        // Act
        $viewData = $this->widget->getViewData();

        // Assert
        $this->assertIsArray($viewData);
        $this->assertArrayHasKey('error', $viewData);
        $this->assertEquals('Terjadi kesalahan saat memuat notifikasi', $viewData['error']);
        $this->assertEmpty($viewData['notifications']);
        $this->assertEquals(0, $viewData['total']);
        $this->assertEquals(0, $viewData['unread']);
    }

    public function test_it_marks_notification_as_read_successfully()
    {
        // Arrange
        $notificationId = 'test_notification_123';

        $this->notificationService->shouldReceive('markAsRead')
            ->once()
            ->with($this->user->id, $notificationId)
            ->andReturn([
                'success' => true,
                'message' => 'Notification marked as read',
            ]);

        $this->app->instance(NotificationService::class, $this->notificationService);

        // Act
        $this->widget->markAsRead($notificationId);

        // Assert - Method should execute without errors
        $this->assertTrue(true);
    }

    public function test_it_handles_mark_as_read_with_unauthenticated_user()
    {
        // Arrange
        Auth::logout();
        $notificationId = 'test_notification_123';

        // Act
        $this->widget->markAsRead($notificationId);

        // Assert - Method should execute without errors (fail silently)
        $this->assertTrue(true);
    }

    public function test_it_handles_mark_as_read_with_empty_notification_id()
    {
        // Arrange
        $notificationId = '';

        // Act
        $this->widget->markAsRead($notificationId);

        // Assert - Method should execute without errors (fail silently)
        $this->assertTrue(true);
    }

    public function test_it_handles_mark_as_read_service_failure()
    {
        // Arrange
        $notificationId = 'test_notification_123';

        $this->notificationService->shouldReceive('markAsRead')
            ->once()
            ->with($this->user->id, $notificationId)
            ->andReturn([
                'success' => false,
                'error' => 'Notification not found',
            ]);

        $this->app->instance(NotificationService::class, $this->notificationService);

        // Act
        $this->widget->markAsRead($notificationId);

        // Assert - Method should execute without errors (fail silently)
        $this->assertTrue(true);
    }

    public function test_it_handles_mark_as_read_exception()
    {
        // Arrange
        $notificationId = 'test_notification_123';

        $this->notificationService->shouldReceive('markAsRead')
            ->once()
            ->with($this->user->id, $notificationId)
            ->andThrow(new \Exception('Service exception'));

        $this->app->instance(NotificationService::class, $this->notificationService);

        // Act
        $this->widget->markAsRead($notificationId);

        // Assert - Method should execute without errors (fail silently)
        $this->assertTrue(true);
    }

    public function test_it_clears_all_notifications_successfully()
    {
        // Arrange
        $this->notificationService->shouldReceive('clearAllNotifications')
            ->once()
            ->with($this->user->id)
            ->andReturn([
                'success' => true,
                'message' => 'All notifications cleared',
            ]);

        $this->app->instance(NotificationService::class, $this->notificationService);

        // Act
        $this->widget->clearAll();

        // Assert - Method should execute without errors
        $this->assertTrue(true);
    }

    public function test_it_handles_clear_all_with_unauthenticated_user()
    {
        // Arrange
        Auth::logout();

        // Act
        $this->widget->clearAll();

        // Assert - Method should execute without errors (fail silently)
        $this->assertTrue(true);
    }

    public function test_it_handles_clear_all_service_failure()
    {
        // Arrange
        $this->notificationService->shouldReceive('clearAllNotifications')
            ->once()
            ->with($this->user->id)
            ->andReturn([
                'success' => false,
                'error' => 'Failed to clear notifications',
            ]);

        $this->app->instance(NotificationService::class, $this->notificationService);

        // Act
        $this->widget->clearAll();

        // Assert - Method should execute without errors (fail silently)
        $this->assertTrue(true);
    }

    public function test_it_handles_clear_all_exception()
    {
        // Arrange
        $this->notificationService->shouldReceive('clearAllNotifications')
            ->once()
            ->with($this->user->id)
            ->andThrow(new \Exception('Service exception'));

        $this->app->instance(NotificationService::class, $this->notificationService);

        // Act
        $this->widget->clearAll();

        // Assert - Method should execute without errors (fail silently)
        $this->assertTrue(true);
    }

    public function test_it_has_correct_polling_interval()
    {
        // Act
        $pollingInterval = $this->widget->getPollingInterval();

        // Assert
        $this->assertEquals('30s', $pollingInterval);
    }

    public function test_it_returns_empty_view_data_structure()
    {
        // Arrange
        $widget = new class extends NotificationWidget {
            public function testGetEmptyViewData($error = '')
            {
                return $this->getEmptyViewData($error);
            }
        };

        // Act
        $emptyData = $widget->testGetEmptyViewData('Test error');

        // Assert
        $this->assertIsArray($emptyData);
        $this->assertArrayHasKey('notifications', $emptyData);
        $this->assertArrayHasKey('total', $emptyData);
        $this->assertArrayHasKey('unread', $emptyData);
        $this->assertArrayHasKey('last_updated', $emptyData);
        $this->assertArrayHasKey('user_id', $emptyData);
        $this->assertArrayHasKey('error', $emptyData);

        $this->assertEmpty($emptyData['notifications']);
        $this->assertEquals(0, $emptyData['total']);
        $this->assertEquals(0, $emptyData['unread']);
        $this->assertEquals('Test error', $emptyData['error']);
    }

    public function test_it_has_correct_widget_properties()
    {
        // Test widget configuration
        $this->assertEquals('filament.petugas.widgets.notification-widget', $this->widget::getView());
        $this->assertEquals(1, $this->widget::getSort());
        $this->assertFalse($this->widget::isLazy());
        $this->assertEquals('full', $this->widget->getColumnSpan());
    }

    public function test_it_handles_service_initialization_failure()
    {
        // Arrange - Mock TelegramService to throw exception during construction
        $this->app->bind(TelegramService::class, function () {
            throw new \Exception('Failed to initialize TelegramService');
        });

        // Act - Widget should handle initialization failure gracefully
        $widget = new NotificationWidget();

        // Assert - Widget should still be created
        $this->assertInstanceOf(NotificationWidget::class, $widget);
    }

    public function test_it_processes_notification_data_correctly()
    {
        // Arrange
        $mockNotifications = [
            [
                'id' => 'notif_1',
                'title' => 'Validation Required',
                'message' => 'Please validate Tindakan #123',
                'priority' => 'high',
                'type' => 'validation_pending',
                'created_at' => now()->toISOString(),
                'read_at' => null,
                'data' => [
                    'type' => 'tindakan',
                    'record_id' => 123,
                    'priority' => 'urgent',
                ],
            ],
        ];

        $this->notificationService->shouldReceive('getUserNotifications')
            ->once()
            ->with($this->user->id, 10)
            ->andReturn([
                'success' => true,
                'notifications' => $mockNotifications,
                'total' => 1,
                'unread' => 1,
            ]);

        $this->app->instance(NotificationService::class, $this->notificationService);

        // Act
        $viewData = $this->widget->getViewData();

        // Assert
        $notification = $viewData['notifications'][0];
        $this->assertEquals('notif_1', $notification['id']);
        $this->assertEquals('Validation Required', $notification['title']);
        $this->assertEquals('high', $notification['priority']);
        $this->assertEquals('validation_pending', $notification['type']);
        $this->assertArrayHasKey('data', $notification);
        $this->assertEquals('urgent', $notification['data']['priority']);
    }

    public function test_it_handles_different_notification_types()
    {
        // Test that widget can handle various notification types
        $mockNotifications = [
            [
                'id' => 'notif_1',
                'title' => 'Validation Pending',
                'message' => 'Validation required',
                'priority' => 'medium',
                'type' => 'validation_pending',
                'created_at' => now()->toISOString(),
                'read_at' => null,
            ],
            [
                'id' => 'notif_2',
                'title' => 'Task Reminder',
                'message' => 'Task reminder',
                'priority' => 'low',
                'type' => 'task_reminder',
                'created_at' => now()->subHour()->toISOString(),
                'read_at' => null,
            ],
            [
                'id' => 'notif_3',
                'title' => 'System Alert',
                'message' => 'System alert',
                'priority' => 'high',
                'type' => 'system_alert',
                'created_at' => now()->subMinutes(30)->toISOString(),
                'read_at' => now()->subMinutes(15)->toISOString(),
            ],
        ];

        $this->notificationService->shouldReceive('getUserNotifications')
            ->once()
            ->with($this->user->id, 10)
            ->andReturn([
                'success' => true,
                'notifications' => $mockNotifications,
                'total' => 3,
                'unread' => 2,
            ]);

        $this->app->instance(NotificationService::class, $this->notificationService);

        // Act
        $viewData = $this->widget->getViewData();

        // Assert
        $this->assertCount(3, $viewData['notifications']);
        $this->assertEquals(3, $viewData['total']);
        $this->assertEquals(2, $viewData['unread']);

        // Check different notification types are handled
        $types = array_column($viewData['notifications'], 'type');
        $this->assertContains('validation_pending', $types);
        $this->assertContains('task_reminder', $types);
        $this->assertContains('system_alert', $types);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
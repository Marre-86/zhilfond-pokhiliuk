<?php

namespace Tests\Feature\Console;

use App\Notifications\NotificationService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TestNotificationCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create the default test user that the command expects
        User::factory()->create([
            'name' => 'Test User',
            'email' => env('TEST_USER_EMAIL'),
            'password' => Hash::make(env('TEST_USER_PASSWORD')),
        ]);
    }

    public function test_sends_notification_via_email_channel_successfully()
    {
        $mockService = $this->mock(NotificationService::class);
        $mockService->shouldReceive('setStrategyByChannel')
            ->with('email')
            ->once();
        $mockService->shouldReceive('notify')
            ->with('Test message', ['email' => 'test@example.com'])
            ->once();

        $this->artisan('notify:test', [
            'channel' => 'email',
            'message' => 'Test message',
            'recipient' => '{"email":"test@example.com"}',
        ])
            ->expectsOutputToContain('Notification created in database with ID:')
            ->expectsOutputToContain('Notification sent successfully (logged).')
            ->assertExitCode(0);
    }

    public function test_sends_notification_via_telegram_channel_successfully()
    {
        $mockService = $this->mock(NotificationService::class);
        $mockService->shouldReceive('setStrategyByChannel')
            ->with('telegram')
            ->once();
        $mockService->shouldReceive('notify')
            ->with('Hello', ['chat_id' => '12345'])
            ->once();

        $this->artisan('notify:test', [
            'channel' => 'telegram',
            'message' => 'Hello',
            'recipient' => '{"chat_id":"12345"}',
        ])
            ->expectsOutputToContain('Notification created in database with ID:')
            ->expectsOutputToContain('Notification sent successfully (logged).')
            ->assertExitCode(0);
    }

    public function test_fails_with_invalid_channel()
    {
        $mockService = $this->mock(NotificationService::class);
        $mockService->shouldReceive('setStrategyByChannel')
            ->with('invalid')
            ->andThrow(new \InvalidArgumentException());

        $this->artisan('notify:test', [
            'channel' => 'invalid',
            'message' => 'Test',
            'recipient' => '{}',
        ])
            ->expectsOutputToContain('The selected channel is invalid')
            ->assertExitCode(1);
    }

    public function test_fails_with_invalid_json_recipient()
    {
        $this->artisan('notify:test', [
            'channel' => 'email',
            'message' => 'Test',
            'recipient' => 'invalid json',
        ])
            ->expectsOutput('Invalid JSON provided for recipient.')
            ->assertExitCode(1);
    }

    public function test_fails_when_notify_throws_runtime_exception()
    {
        $mockService = $this->mock(NotificationService::class);
        $mockService->shouldReceive('setStrategyByChannel')
            ->with('email')
            ->once();
        $mockService->shouldReceive('notify')
            ->with('Test', ['email' => 'test@example.com'])
            ->andThrow(new \RuntimeException('Failed to send'));

        $this->artisan('notify:test', [
            'channel' => 'email',
            'message' => 'Test',
            'recipient' => '{"email":"test@example.com"}',
        ])
            ->expectsOutputToContain('Notification created in database with ID:')
            ->expectsOutput('Failed to send')
            ->assertExitCode(1);
    }
}
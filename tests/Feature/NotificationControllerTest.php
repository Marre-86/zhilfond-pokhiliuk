<?php

namespace Tests\Feature;

use App\Enums\NotificationStatus;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_stores_a_new_notification_via_api()
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/store-notification', [
            'message' => 'Test notification message',
            'user_id' => $user->id,
            'status' => NotificationStatus::PENDING->value,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Notification created successfully.',
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'message',
                    'user_id',
                    'status',
                    'created_at',
                    'updated_at',
                ],
                'message',
            ]);

        $this->assertDatabaseHas('notifications', [
            'message' => 'Test notification message',
            'user_id' => $user->id,
            'status' => NotificationStatus::PENDING->value,
        ]);
    }

    public function test_validates_required_fields_when_storing_notification()
    {
        $response = $this->postJson('/api/store-notification', []);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ])
            ->assertJsonValidationErrors(['message', 'user_id']);
    }

    public function test_validates_user_exists_when_storing_notification()
    {
        $response = $this->postJson('/api/store-notification', [
            'message' => 'Test message',
            'user_id' => 999, // Non-existent user
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['user_id']);
    }

    public function test_uses_default_pending_status_when_not_provided()
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/store-notification', [
            'message' => 'Test message',
            'user_id' => $user->id,
            // status not provided
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('notifications', [
            'message' => 'Test message',
            'user_id' => $user->id,
            'status' => NotificationStatus::PENDING->value,
        ]);
    }

    public function test_returns_notification_status_by_id()
    {
        $user = User::factory()->create();
        $notification = Notification::create([
            'message' => 'Test notification',
            'user_id' => $user->id,
            'status' => NotificationStatus::SENT->value,
        ]);

        $response = $this->getJson("/api/notification-status/{$notification->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Notification status retrieved successfully.',
                'data' => [
                    'id' => $notification->id,
                    'status' => NotificationStatus::SENT->value,
                    'status_label' => 'sent',
                ],
            ]);
    }

    public function test_returns_404_when_notification_not_found()
    {
        $response = $this->getJson('/api/notification-status/999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Notification not found.',
            ]);
    }

    public function test_returns_correct_status_labels_for_all_status_values()
    {
        $user = User::factory()->create();
        
        $statuses = [
            NotificationStatus::PENDING,
            NotificationStatus::SENT,
            NotificationStatus::ERROR,
        ];

        foreach ($statuses as $status) {
            $notification = Notification::create([
                'message' => "21221Test {$status->label()}",
                'user_id' => $user->id,
                'status' => $status->value,
            ]);

            $dd = Notification::all();

            \Illuminate\Support\Facades\Log::info($dd);

            $response = $this->getJson("/api/notification-status/{$notification->id}");

            \Illuminate\Support\Facades\Log::info($response->json());


            $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'status' => $status->value,
                        'status_label' => $status->label(),
                    ],
                ]);
        }
    }

    public function test_returns_user_notification_history()
    {
        $user = User::factory()->create();
        
        // Create some notifications for the user
        Notification::create([
            'message' => 'First notification',
            'user_id' => $user->id,
            'status' => NotificationStatus::SENT->value,
            'channel' => 'email',
        ]);
        
        Notification::create([
            'message' => 'Second notification',
            'user_id' => $user->id,
            'status' => NotificationStatus::PENDING->value,
            'channel' => 'telegram',
        ]);
        
        // Create a notification for a different user (should not appear)
        $otherUser = User::factory()->create();
        Notification::create([
            'message' => 'Other user notification',
            'user_id' => $otherUser->id,
            'status' => NotificationStatus::SENT->value,
            'channel' => 'email',
        ]);

        $response = $this->getJson("/api/user/{$user->id}/notifications");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'User notification history retrieved successfully.',
                'data' => [
                    'user_id' => $user->id,
                    'total' => 2,
                ],
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user_id',
                    'notifications' => [
                        '*' => [
                            'id',
                            'message',
                            'status',
                            'status_label',
                            'channel',
                            'created_at',
                            'updated_at',
                        ],
                    ],
                    'total',
                ],
                'message',
            ]);
    }

    public function test_filters_user_notifications_by_status()
    {
        $user = User::factory()->create();
        
        Notification::create([
            'message' => 'Sent notification',
            'user_id' => $user->id,
            'status' => NotificationStatus::SENT->value,
            'channel' => 'email',
        ]);
        
        Notification::create([
            'message' => 'Pending notification',
            'user_id' => $user->id,
            'status' => NotificationStatus::PENDING->value,
            'channel' => 'email',
        ]);

        $response = $this->getJson("/api/user/{$user->id}/notifications?status=" . NotificationStatus::SENT->value);

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertCount(1, $data['notifications']);
        $this->assertEquals(NotificationStatus::SENT->value, $data['notifications'][0]['status']);
    }

    public function test_filters_user_notifications_by_channel()
    {
        $user = User::factory()->create();
        
        Notification::create([
            'message' => 'Email notification',
            'user_id' => $user->id,
            'status' => NotificationStatus::SENT->value,
            'channel' => 'email',
        ]);
        
        Notification::create([
            'message' => 'Telegram notification',
            'user_id' => $user->id,
            'status' => NotificationStatus::SENT->value,
            'channel' => 'telegram',
        ]);

        $response = $this->getJson("/api/user/{$user->id}/notifications?channel=email");

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertCount(1, $data['notifications']);
        $this->assertEquals('email', $data['notifications'][0]['channel']);
    }

    public function test_filters_user_notifications_by_status_and_channel()
    {
        $user = User::factory()->create();
        
        Notification::create([
            'message' => 'Email sent',
            'user_id' => $user->id,
            'status' => NotificationStatus::SENT->value,
            'channel' => 'email',
        ]);
        
        Notification::create([
            'message' => 'Email pending',
            'user_id' => $user->id,
            'status' => NotificationStatus::PENDING->value,
            'channel' => 'email',
        ]);
        
        Notification::create([
            'message' => 'Telegram sent',
            'user_id' => $user->id,
            'status' => NotificationStatus::SENT->value,
            'channel' => 'telegram',
        ]);

        $response = $this->getJson("/api/user/{$user->id}/notifications?status=" . NotificationStatus::SENT->value . "&channel=email");

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertCount(1, $data['notifications']);
        $this->assertEquals(NotificationStatus::SENT->value, $data['notifications'][0]['status']);
        $this->assertEquals('email', $data['notifications'][0]['channel']);
    }

    public function test_returns_404_when_user_not_found()
    {
        $response = $this->getJson('/api/user/999/notifications');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'User not found.',
            ]);
    }

    public function test_validates_filter_parameters()
    {
        $user = User::factory()->create();

        $response = $this->getJson("/api/user/{$user->id}/notifications?status=invalid&channel=invalid");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ])
            ->assertJsonValidationErrors(['status', 'channel']);
    }
}
<?php

namespace App\Http\Controllers;

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Models\Notification;
use App\Services\NotificationCreator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class NotificationController extends Controller
{
    /**
     * Store a new notification from API request.
     *
     * @param Request $request
     * @param NotificationCreator $notificationCreator
     * @return JsonResponse
     */
    public function storeFromApi(Request $request, NotificationCreator $notificationCreator): JsonResponse
    {
        // Validate request data using shared validation rules
        $validator = Validator::make($request->all(), NotificationCreator::validationRules());

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        // Create notification record
        try {
            $notification = $notificationCreator->create($request->all());

            return response()->json([
                'success' => true,
                'data'    => $notification,
                'message' => 'Notification created successfully.',
            ], 201);
        } catch (InvalidArgumentException $e) {
            // This includes validation errors from creator (e.g., user does not exist)
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 422);
        } catch (\Exception $e) {
            // Log the error (optional)
            // \Log::error('Failed to create notification: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create notification. Please try again later.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get the status of a requested notification.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function status(int $id): JsonResponse
    {
        try {
            $notification = Notification::find($id);

            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data'    => [
                    'id'           => $notification->id,
                    'status'       => $notification->status->value,
                    'status_label' => $notification->status->label(),
                ],
                'message' => 'Notification status retrieved successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve notification status.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get notification history for a user with optional filtering by status and channel.
     *
     * @param Request $request
     * @param int $userId
     * @return JsonResponse
     */
    public function userHistory(Request $request, int $userId): JsonResponse
    {
        try {
            // Validate query parameters
            $validator = Validator::make($request->query(), [
                'status' => ['nullable', 'integer', Rule::in(NotificationStatus::values())],
                'channel' => ['nullable', 'string', Rule::in(NotificationChannel::values())],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors'  => $validator->errors(),
                ], 422);
            }

            // Check if user exists
            $userExists = \App\Models\User::where('id', $userId)->exists();
            if (!$userExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found.',
                ], 404);
            }

            // Build query
            $query = Notification::where('user_id', $userId);

            // Apply status filter if provided
            if ($request->has('status') && $request->query('status') !== null) {
                $query->where('status', $request->query('status'));
            }

            // Apply channel filter if provided
            if ($request->has('channel') && $request->query('channel') !== null) {
                $query->where('channel', $request->query('channel'));
            }

            // Get notifications with pagination
            $notifications = $query->orderBy('created_at', 'desc')->get();

            // Transform data for response
            $transformedNotifications = $notifications->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'message' => $notification->message,
                    'status' => $notification->status->value,
                    'status_label' => $notification->status->label(),
                    'channel' => $notification->channel,
                    'created_at' => $notification->created_at,
                    'updated_at' => $notification->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'user_id' => $userId,
                    'notifications' => $transformedNotifications,
                    'total' => $notifications->count(),
                ],
                'message' => 'User notification history retrieved successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user notification history.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}

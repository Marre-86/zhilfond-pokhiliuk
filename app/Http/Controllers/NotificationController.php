<?php

namespace App\Http\Controllers;

use App\Enums\NotificationStatus;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class NotificationController extends Controller
{
    /**
     * Store a new notification from API request.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function storeFromApi(Request $request): JsonResponse
    {
        // Validate request data
        $validator = Validator::make($request->all(), [
            'message' => ['required', 'string', 'max:500'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'status'  => ['nullable', 'integer', Rule::in(NotificationStatus::values())],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        // Create notification record
        try {
            $notification = Notification::create([
                'message' => $request->input('message'),
                'user_id' => $request->input('user_id'),
                'status'  => $request->input('status', NotificationStatus::PENDING->value),
            ]);

            return response()->json([
                'success' => true,
                'data'    => $notification,
                'message' => 'Notification created successfully.',
            ], 201);
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
}

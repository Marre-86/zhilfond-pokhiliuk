<?php

namespace App\Services;

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class NotificationCreator
{
    /**
     * Get validation rules for notification creation.
     *
     * @return array
     */
    public static function validationRules(): array
    {
        return [
            'message' => ['required', 'string', 'max:500'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'status'  => ['nullable', 'integer', Rule::in(NotificationStatus::values())],
            'channel' => ['nullable', 'string', Rule::in(NotificationChannel::values())],
        ];
    }

    /**
     * Create a notification from validated data.
     *
     * @param array $data
     * @return Notification
     * @throws InvalidArgumentException
     */
    public function create(array $data): Notification
    {
        $validated = $this->validate($data);

        // Ensure user exists
        if (!User::where('id', $validated['user_id'])->exists()) {
            throw new InvalidArgumentException('User does not exist.');
        }

        return Notification::create([
            'message' => $validated['message'],
            'user_id' => $validated['user_id'],
            'status'  => $validated['status'] ?? NotificationStatus::PENDING->value,
            'channel' => $validated['channel'] ?? null,
        ]);
    }

    /**
     * Validate notification data.
     *
     * @param array $data
     * @return array
     * @throws InvalidArgumentException
     */
    public function validate(array $data): array
    {
        $validator = Validator::make($data, self::validationRules());

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        return $validator->validated();
    }
}

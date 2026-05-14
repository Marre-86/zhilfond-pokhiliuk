<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NotificationController;

Route::post('/store-notification', [NotificationController::class, 'storeFromApi']);
Route::get('/notification-status/{id}', [NotificationController::class, 'status']);
Route::get('/user/{userId}/notifications', [NotificationController::class, 'userHistory']);

<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\HelpdeskController;

// Public auth routes with rate limiting
Route::post('/auth/login', [AuthController::class, 'login'])
    ->middleware(\App\Http\Middleware\ThrottleAuth::class . ':5,15'); // 5 attempts per 15 minutes
Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::post('/auth/password/email', [AuthController::class, 'sendPasswordResetLink'])
    ->middleware(\App\Http\Middleware\ThrottleAuth::class . ':5,15'); // 5 attempts per 15 minutes
Route::post('/auth/password/reset', [AuthController::class, 'resetPassword'])
    ->middleware(\App\Http\Middleware\ThrottleAuth::class . ':5,15'); // 5 attempts per 15 minutes

// Optional MFA endpoints (bonus)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/mfa/setup', [AuthController::class, 'setupMfa']);
    Route::post('/auth/mfa/confirm', [AuthController::class, 'confirmMfaSetup']);
    Route::post('/auth/mfa/disable', [AuthController::class, 'disableMfa']);
});
// Verification endpoint is public, since the user may not have an API token yet
Route::post('/auth/mfa/verify', [AuthController::class, 'verifyMfa'])
    ->middleware(\App\Http\Middleware\ThrottleAuth::class . ':5,15'); // 5 attempts per 15 minutes

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Debug endpoint to check role and gate
    Route::get('/debug/role', function (Request $request) {
        $user = $request->user();
        $user->refresh(); // Refresh from database

        // Test gate closure directly
        $gateResult = \Illuminate\Support\Facades\Gate::forUser($user)->allows('act-as-helpdesk-agent');

        // Manual check
        $manualCheck = $user->role === 'helpdesk_agent';

        return response()->json([
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
            'role_type' => gettype($user->role),
            'role_raw' => $user->getRawOriginal('role'),
            'manual_check' => $manualCheck,
            'can_act_as_helpdesk_agent' => $request->user()->can('act-as-helpdesk-agent'),
            'gate_check' => $gateResult,
            'gate_definition_exists' => \Illuminate\Support\Facades\Gate::has('act-as-helpdesk-agent'),
        ]);
    });

    // Events CRUD scoped to authenticated user
    Route::get('/events', [EventController::class, 'index']);
    Route::post('/events', [EventController::class, 'store']);
    Route::put('/events/{event}', [EventController::class, 'update']);
    Route::delete('/events/{event}', [EventController::class, 'destroy']);

    // Helpdesk (end-user side)
    Route::get('/helpdesk/my-chats', [HelpdeskController::class, 'getUserChats']);
    Route::post('/helpdesk/chats', [HelpdeskController::class, 'startChat']);
    Route::post('/helpdesk/chats/{chat}/messages', [HelpdeskController::class, 'addMessageFromUser']);
    Route::post('/helpdesk/chats/{chat}/close', [HelpdeskController::class, 'closeChat']);
    Route::get('/helpdesk/chats/{chat}', [HelpdeskController::class, 'showChat']);
});

// Helpdesk agent routes (requires helpdesk_agent role)
Route::middleware(['auth:sanctum', \App\Http\Middleware\EnsureHelpdeskAgent::class])->group(function () {
    Route::get('/helpdesk/chats', [HelpdeskController::class, 'listChats']);
    Route::get('/helpdesk/chats/{chat}', [HelpdeskController::class, 'showChat']);
    Route::post('/helpdesk/chats/{chat}/agent-messages', [HelpdeskController::class, 'addMessageFromAgent']);
    Route::post('/helpdesk/chats/{chat}/transfer', [HelpdeskController::class, 'transferToHuman']);
    Route::post('/helpdesk/chats/{chat}/close', [HelpdeskController::class, 'closeChatByAgent']);
});



<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $events = Event::where('user_id', $request->user()->id)
            ->orderBy('occurs_at', 'asc')
            ->get();

        // AuditLogService::log('view', $request->user(), 'events', null, ['count' => $events->count()]);

        return response()->json($events);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'occurs_at' => 'required|date',
            'description' => 'nullable|string',
        ]);

        $event = Event::create([
            'user_id' => $request->user()->id,
            ...$validated,
        ]);

        AuditLogService::logCreate($request->user(), 'events', $event->id, [
            'title' => $event->title,
            'occurs_at' => $event->occurs_at,
        ]);

        return response()->json($event, Response::HTTP_CREATED);
    }

    public function update(Request $request, Event $event)
    {
        $this->authorize('update', $event);

        $validated = $request->validate([
            'description' => 'nullable|string',
        ]);

        $oldDescription = $event->description;
        $event->update($validated);

        AuditLogService::logUpdate($request->user(), 'events', $event->id, [
            'field' => 'description',
            'old_value_length' => strlen($oldDescription ?? ''),
            'new_value_length' => strlen($event->description ?? ''),
        ]);

        return response()->json($event);
    }

    public function destroy(Request $request, Event $event)
    {
        $this->authorize('delete', $event);

        // Log before deletion (trait logs after, but we want to capture data)
        AuditLogService::logDelete($request->user(), 'events', $event->id, [
            'title' => $event->title,
            'occurs_at' => $event->occurs_at,
        ]);

        $event->delete();

        return response()->json([]);
    }
}



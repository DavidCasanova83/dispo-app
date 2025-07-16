<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LogController extends Controller
{
    /**
     * Display a listing of activity logs.
     */
    public function index(Request $request): View
    {
        $query = ActivityLog::with('user')
            ->orderBy('created_at', 'desc');

        // Filter by event type
        if ($request->filled('event_type')) {
            $query->where('event_type', $request->event_type);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by entity type
        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->entity_type);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search in message
        if ($request->filled('search')) {
            $query->where('message', 'like', '%' . $request->search . '%');
        }

        $logs = $query->paginate(50);

        // Get filter options
        $eventTypes = ActivityLog::distinct('event_type')->pluck('event_type');
        $entityTypes = ActivityLog::distinct('entity_type')->whereNotNull('entity_type')->pluck('entity_type');
        $statuses = ['success', 'error', 'warning', 'info'];

        // Get statistics
        $stats = [
            'total' => ActivityLog::count(),
            'today' => ActivityLog::whereDate('created_at', today())->count(),
            'errors' => ActivityLog::where('status', 'error')->count(),
            'by_status' => ActivityLog::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray(),
        ];

        return view('logs.index', compact(
            'logs',
            'eventTypes',
            'entityTypes',
            'statuses',
            'stats'
        ));
    }

    /**
     * Show the details of a specific log entry.
     */
    public function show(ActivityLog $log): View
    {
        $log->load('user');
        
        return view('logs.show', compact('log'));
    }

    /**
     * Clear old logs (older than 30 days).
     */
    public function clear(Request $request)
    {
        $days = $request->input('days', 30);
        
        $deletedCount = ActivityLog::where('created_at', '<', now()->subDays($days))->delete();
        
        // Log this action
        ActivityLog::logActivity(
            'system',
            'logs_cleared',
            'system',
            null,
            ['deleted_count' => $deletedCount, 'days' => $days],
            "Suppression de {$deletedCount} logs de plus de {$days} jours",
            'success',
            auth()->id()
        );

        return redirect()->route('logs.index')
            ->with('success', "Suppression de {$deletedCount} logs de plus de {$days} jours.");
    }
}

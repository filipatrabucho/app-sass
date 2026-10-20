<?php

namespace App\Core\Audit\Http\Controllers;

use App\Core\Audit\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AuditLogController
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('core.audit.view');
        $request->validate([
            'auditable_type' => ['nullable', 'string', 'max:255'],
            'auditable_id' => ['nullable', 'string', 'max:50'],
            'action' => ['nullable', 'in:created,updated,deleted,restored'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $page = AuditLog::query()
            ->with('user:id,name')
            ->when($request->input('auditable_type'), fn ($q, $t) => $q->where('auditable_type', $t))
            ->when($request->input('auditable_id'), fn ($q, $id) => $q->where('auditable_id', $id))
            ->when($request->input('action'), fn ($q, $a) => $q->where('action', $a))
            ->latest('id')
            ->paginate($request->integer('per_page', 20));

        return response()->json([
            'data' => collect($page->items())->map(fn (AuditLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'auditable_type' => $log->auditable_type,
                'auditable_id' => $log->auditable_id,
                'old_values' => $log->old_values,
                'new_values' => $log->new_values,
                'user' => $log->user?->name,
                'ip' => $log->ip,
                'created_at' => $log->created_at?->toIso8601String(),
            ]),
            'meta' => [
                'current_page' => $page->currentPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'last_page' => $page->lastPage(),
            ],
        ]);
    }
}

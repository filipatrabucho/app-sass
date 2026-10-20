<?php

namespace App\Core\Departments\Http\Controllers;

use App\Core\Departments\Models\Position;
use App\Core\Http\ApiException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class PositionController
{
    public function index(): JsonResponse
    {
        $positions = Position::query()->withCount('employees')->orderBy('name')->get();

        return response()->json(['data' => $positions->map(fn ($p) => [
            'id' => $p->id, 'name' => $p->name, 'employees_count' => $p->employees_count,
        ])]);
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('core.positions.manage');
        $data = $request->validate(['name' => ['required', 'string', 'max:150', Rule::unique(Position::class)]]);

        return response()->json(['data' => Position::create($data)->only('id', 'name')], 201);
    }

    public function update(Request $request, Position $position): JsonResponse
    {
        Gate::authorize('core.positions.manage');
        $data = $request->validate(['name' => ['required', 'string', 'max:150', Rule::unique(Position::class)->ignore($position->id)]]);
        $position->update($data);

        return response()->json(['data' => $position->only('id', 'name')]);
    }

    public function destroy(Position $position): Response
    {
        Gate::authorize('core.positions.manage');

        if ($position->employees()->exists()) {
            throw new ApiException(__('departments.position_in_use'), 'POSITION_IN_USE', 422);
        }
        $position->delete();

        return response()->noContent();
    }
}

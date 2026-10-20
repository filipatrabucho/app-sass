<?php

namespace App\Core\Holidays\Http\Controllers;

use App\Core\Holidays\HolidayService;
use App\Core\Holidays\Http\Requests\HolidayRequest;
use App\Core\Holidays\Models\Holiday;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class HolidayController
{
    public function __construct(private readonly HolidayService $holidays) {}

    /**
     * Feriados efetivos de um ano (recorrentes projetados). Visível a todos; gestão pelo RH.
     */
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'region' => ['nullable', 'string', 'max:100'],
        ]);

        return response()->json([
            'data' => $this->holidays->forYear((int) ($data['year'] ?? now()->year), $data['region'] ?? null),
        ]);
    }

    public function store(HolidayRequest $request): JsonResponse
    {
        Gate::authorize('core.holidays.manage');

        return response()->json(['data' => $this->present(Holiday::create($request->validated()))], 201);
    }

    public function update(HolidayRequest $request, Holiday $holiday): JsonResponse
    {
        Gate::authorize('core.holidays.manage');
        $holiday->update($request->validated());

        return response()->json(['data' => $this->present($holiday)]);
    }

    public function destroy(Holiday $holiday): Response
    {
        Gate::authorize('core.holidays.manage');
        $holiday->delete();

        return response()->noContent();
    }

    public function importNational(Request $request): JsonResponse
    {
        Gate::authorize('core.holidays.manage');
        $data = $request->validate(['year' => ['required', 'integer', 'between:2000,2100']]);

        $created = $this->holidays->importNational((int) $data['year']);

        return response()->json(['message' => trans_choice('holidays.imported', $created, ['count' => $created]), 'created' => $created]);
    }

    private function present(Holiday $holiday): array
    {
        return [
            'id' => $holiday->id,
            'date' => $holiday->date->toDateString(),
            'name' => $holiday->name,
            'type' => $holiday->type,
            'region' => $holiday->region,
            'recurring' => $holiday->recurring,
        ];
    }
}

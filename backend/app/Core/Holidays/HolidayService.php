<?php

namespace App\Core\Holidays;

use App\Core\Holidays\Models\Holiday;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Feriados efetivos de um período: não recorrentes na data registada + recorrentes projetados em cada ano.
 * É o contrato usado pelos módulos (ex.: cálculo de dias úteis nas Férias).
 */
class HolidayService
{
    /**
     * @return Collection<int, array{id: int, date: string, name: string, type: string, region: ?string, recurring: bool}>
     */
    public function between(Carbon $from, Carbon $to, ?string $region = null): Collection
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->startOfDay();

        $holidays = Holiday::query()
            ->when($region !== null, fn ($q) => $q->where(fn ($q) => $q->whereNull('region')->orWhere('region', $region)))
            ->where(fn ($q) => $q->where('recurring', true)->orWhereBetween('date', [$from->toDateString(), $to->toDateString()]))
            ->get();

        $result = collect();
        foreach ($holidays as $holiday) {
            $years = $holiday->recurring ? range($from->year, $to->year) : [$holiday->date->year];
            foreach ($years as $year) {
                $date = $holiday->recurring
                    ? Carbon::create($year, $holiday->date->month, min($holiday->date->day, Carbon::create($year, $holiday->date->month)->daysInMonth))
                    : $holiday->date;
                if ($date->betweenIncluded($from, $to)) {
                    $result->push([
                        'id' => $holiday->id,
                        'date' => $date->toDateString(),
                        'name' => $holiday->name,
                        'type' => $holiday->type,
                        'region' => $holiday->region,
                        'recurring' => $holiday->recurring,
                    ]);
                }
            }
        }

        return $result->sortBy('date')->values();
    }

    /**
     * @return Collection<int, array{id: int, date: string, name: string, type: string, region: ?string, recurring: bool}>
     */
    public function forYear(int $year, ?string $region = null): Collection
    {
        return $this->between(Carbon::create($year, 1, 1), Carbon::create($year, 12, 31), $region);
    }

    /**
     * Importa os feriados nacionais de um ano (idempotente). Devolve quantos foram criados.
     */
    public function importNational(int $year): int
    {
        $created = 0;

        foreach (PortugueseHolidays::forYear($year) as $holiday) {
            $exists = $holiday['recurring']
                ? Holiday::query()->where('recurring', true)->where('type', 'national')->where('name', $holiday['name'])->exists()
                : Holiday::query()->whereDate('date', $holiday['date'])->where('name', $holiday['name'])->exists();

            if (! $exists) {
                Holiday::create([...$holiday, 'type' => 'national']);
                $created++;
            }
        }

        return $created;
    }
}

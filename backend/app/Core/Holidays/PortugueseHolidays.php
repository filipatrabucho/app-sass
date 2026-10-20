<?php

namespace App\Core\Holidays;

use Illuminate\Support\Carbon;

/**
 * Feriados nacionais obrigatórios em Portugal (Código do Trabalho, art. 234.º).
 * O Carnaval e os feriados municipais não são obrigatórios: cada empresa adiciona-os.
 */
class PortugueseHolidays
{
    /**
     * @return list<array{date: string, name: string, recurring: bool}>
     */
    public static function forYear(int $year): array
    {
        $easter = self::easter($year);

        $fixed = [
            '01-01' => 'Ano Novo',
            '04-25' => 'Dia da Liberdade',
            '05-01' => 'Dia do Trabalhador',
            '06-10' => 'Dia de Portugal, de Camões e das Comunidades Portuguesas',
            '08-15' => 'Assunção de Nossa Senhora',
            '10-05' => 'Implantação da República',
            '11-01' => 'Dia de Todos os Santos',
            '12-01' => 'Restauração da Independência',
            '12-08' => 'Imaculada Conceição',
            '12-25' => 'Natal',
        ];

        $holidays = [];
        foreach ($fixed as $monthDay => $name) {
            $holidays[] = ['date' => "{$year}-{$monthDay}", 'name' => $name, 'recurring' => true];
        }

        // Móveis (dependem da Páscoa): ficam associados ao ano.
        $holidays[] = ['date' => $easter->copy()->subDays(2)->toDateString(), 'name' => 'Sexta-feira Santa', 'recurring' => false];
        $holidays[] = ['date' => $easter->toDateString(), 'name' => 'Páscoa', 'recurring' => false];
        $holidays[] = ['date' => $easter->copy()->addDays(60)->toDateString(), 'name' => 'Corpo de Deus', 'recurring' => false];

        usort($holidays, fn ($a, $b) => strcmp($a['date'], $b['date']));

        return $holidays;
    }

    /**
     * Domingo de Páscoa (algoritmo anónimo gregoriano; não depende da extensão calendar).
     */
    public static function easter(int $year): Carbon
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return Carbon::create($year, $month, $day)->startOfDay();
    }
}

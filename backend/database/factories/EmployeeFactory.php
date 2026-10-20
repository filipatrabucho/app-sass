<?php

namespace Database\Factories;

use App\Core\Employees\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        $faker = \Faker\Factory::create('pt_PT');
        $first = $faker->firstName();
        $last = $faker->lastName();

        return [
            'employee_number' => (string) $faker->unique()->numberBetween(1000, 99999),
            'first_name' => $first,
            'last_name' => $last,
            'email' => strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', "{$first}.{$last}")).'.'.$faker->unique()->numberBetween(1, 9999).'@exemplo.pt',
            'nif' => (string) $faker->numberBetween(100000000, 299999999),
            'birth_date' => $faker->dateTimeBetween('-60 years', '-20 years')->format('Y-m-d'),
            'phone' => '9'.$faker->numberBetween(10000000, 69999999),
            'address' => [
                'street' => $faker->streetAddress(),
                'postal_code' => $faker->numberBetween(1000, 9999).'-'.$faker->numberBetween(100, 999),
                'city' => $faker->city(),
                'country' => 'Portugal',
            ],
            'iban' => 'PT50'.$faker->numerify('#####################'),
            'hire_date' => $faker->dateTimeBetween('-10 years', '-1 month')->format('Y-m-d'),
            'status' => Employee::STATUS_ACTIVE,
        ];
    }
}

<?php

return [
    'parent_cycle' => 'Um departamento não pode estar dentro de si próprio ou de um subdepartamento.',
    'not_empty' => 'Não é possível apagar: o departamento tem colaboradores ou subdepartamentos.',
    'position_in_use' => 'Não é possível apagar: há colaboradores com este cargo.',
    'attributes' => [
        'name' => 'nome',
        'parent_id' => 'departamento superior',
        'manager_employee_id' => 'responsável',
    ],
];

<?php

namespace Database\Seeders;

use App\Models\TriageQuestion;
use Illuminate\Database\Seeder;

class TriageQuestionSeeder extends Seeder
{
    public function run(): void
    {
        $questions = [
            // Módulo: policies
            [
                'module_slug' => 'policies',
                'key' => 'has_employees',
                'label' => '¿La empresa tiene empleados?',
                'type' => 'boolean',
                'order' => 1,
            ],
            [
                'module_slug' => 'policies',
                'key' => 'has_digital_presence',
                'label' => '¿La empresa tiene presencia digital (sitio web, app)?',
                'type' => 'boolean',
                'order' => 2,
            ],
            [
                'module_slug' => 'policies',
                'key' => 'employee_count',
                'label' => 'Número de empleados',
                'type' => 'number',
                'required_condition' => [
                    'key' => 'has_employees',
                    'value' => true,
                ],
                'order' => 3,
            ],
            // Módulo: arco
            [
                'module_slug' => 'arco',
                'key' => 'has_arco_portal',
                'label' => '¿La empresa tiene portal de solicitudes ARCO?',
                'type' => 'boolean',
                'order' => 1,
            ],
        ];

        foreach ($questions as $question) {
            TriageQuestion::create($question);
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\TriageQuestion;
use Illuminate\Database\Seeder;

class TriageQuestionSeeder extends Seeder
{
    public function run(): void
    {
        $questions = [
            [
                'module_slug' => 'policies',
                'key' => 'has_digital_presence',
                'label' => '¿La empresa cuenta con sitio web o aplicación móvil?',
                'description' => 'Necesario para determinar si requiere políticas de privacidad web y de cookies bajo la Ley 21.719.',
                'type' => 'boolean',
                'order' => 1,
                'is_active' => true,
            ],
            [
                'module_slug' => 'policies',
                'key' => 'has_employees',
                'label' => '¿La empresa tiene trabajadores contratados?',
                'description' => 'Determina la necesidad de políticas laborales de privacidad y protección de datos de trabajadores.',
                'type' => 'boolean',
                'order' => 2,
                'is_active' => true,
            ],
            [
                'module_slug' => 'policies',
                'key' => 'employee_count',
                'label' => 'Número aproximado de trabajadores',
                'description' => 'Ayuda a evaluar la escala del tratamiento y si se requieren Evaluaciones de Impacto (PIA) obligatorias.',
                'type' => 'number',
                'required_condition' => [
                    'key' => 'has_employees',
                    'value' => true,
                ],
                'order' => 3,
                'is_active' => true,
            ],
            [
                'module_slug' => 'arco',
                'key' => 'has_arco_portal',
                'label' => '¿La empresa dispone de un portal de solicitudes ARCO+P?',
                'description' => 'El portal ARCO+P permite a los titulares ejercer sus derechos de Acceso, Rectificación, Cancelación, Oposición y Portabilidad.',
                'type' => 'boolean',
                'order' => 1,
                'is_active' => true,
            ],
        ];

        foreach ($questions as $question) {
            TriageQuestion::updateOrCreate(
                ['module_slug' => $question['module_slug'], 'key' => $question['key']],
                $question
            );
        }
    }
}

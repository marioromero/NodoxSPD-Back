<?php

namespace Database\Seeders;

use App\Models\ConsentPurpose;
use Illuminate\Database\Seeder;

/**
 * Puebla el catálogo maestro de fines legales (Ley 21.719).
 *
 * Usa updateOrCreate con slug como llave para que el seeder sea idempotente:
 * ejecutarlo multiples veces actualiza los textos sin duplicar registros.
 */
class ConsentPurposesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $purposes = [
            [
                'slug' => 'necessary_technical',
                'category' => 'gestion_general',
                'label' => 'Cookies técnicas y necesarias',
                'description' => 'Indispensables para el funcionamiento básico del sitio: mantienen tu sesión activa, recuerdan el contenido de tu carrito y protegen contra fraudes. No pueden desactivarse porque el sitio no funcionaría sin ellas.',
                'legal_basis' => 'legitimate_interest',
                'requires_consent' => false,
                'default_value' => true,
                'widget_action' => null,
                'display_order' => 0,
            ],
            [
                'slug' => 'analytics_behavior',
                'category' => 'analisis_comportamiento',
                'label' => 'Análisis de visitas y comportamiento',
                'description' => 'Nos permiten entender cómo navegas por el sitio: qué páginas visitas, cuánto tiempo permaneces y desde dónde llegas. Esta información se usa únicamente para mejorar el sitio y nunca para identificarte personalmente.',
                'legal_basis' => 'consent',
                'requires_consent' => true,
                'default_value' => false,
                'widget_action' => 'load_analytics_scripts',
                'display_order' => 1,
            ],
            [
                'slug' => 'analytics_preferences',
                'category' => 'analisis_comportamiento',
                'label' => 'Recordar preferencias y personalización',
                'description' => 'Permiten que el sitio recuerde tus preferencias (idioma, región, configuración de visualización) para ofrecerte una experiencia más consistente en futuras visitas.',
                'legal_basis' => 'consent',
                'requires_consent' => true,
                'default_value' => false,
                'widget_action' => 'load_functional_scripts',
                'display_order' => 2,
            ],
            [
                'slug' => 'marketing_direct',
                'category' => 'comercial_marketing',
                'label' => 'Publicidad y marketing directo',
                'description' => 'Permiten mostrarte anuncios relevantes en este sitio y en otras plataformas (como Instagram, Google o TikTok) basados en tus intereses. Sin estas cookies, seguirás viendo anuncios, pero serán menos relevantes para ti.',
                'legal_basis' => 'consent',
                'requires_consent' => true,
                'default_value' => false,
                'widget_action' => 'load_marketing_scripts',
                'display_order' => 3,
            ],
            [
                'slug' => 'marketing_profiling',
                'category' => 'comercial_marketing',
                'label' => 'Elaboración de perfil de intereses',
                'description' => 'Permiten construir un perfil sobre tus preferencias e intereses a partir de tu comportamiento de navegación, para mostrarte contenido y ofertas más relevantes. Tus datos pueden compartirse con plataformas de publicidad.',
                'legal_basis' => 'consent',
                'requires_consent' => true,
                'default_value' => false,
                'widget_action' => 'consent_mode_v2_ads',
                'display_order' => 4,
            ],
            [
                'slug' => 'functional_third_party',
                'category' => 'analisis_comportamiento',
                'label' => 'Funcionalidades de servicios externos',
                'description' => 'Activan servicios integrados de terceros como chats de atención al cliente, reproductores de video (YouTube, Vimeo) y mapas interactivos (Google Maps). Sin estas cookies, esos elementos no se cargarán en el sitio.',
                'legal_basis' => 'consent',
                'requires_consent' => true,
                'default_value' => false,
                'widget_action' => 'load_functional_scripts',
                'display_order' => 5,
            ],
            [
                'slug' => 'service_improvement',
                'category' => 'gestion_general',
                'label' => 'Mejora de servicios y seguridad',
                'description' => 'Tratamiento basado en el interés legítimo de la empresa para garantizar la seguridad de la red, prevenir fraudes y mejorar la calidad de los servicios prestados. Tienes derecho a oponerte a este tratamiento según la Ley 21.719.',
                'legal_basis' => 'legitimate_interest',
                'requires_consent' => false,
                'default_value' => true,
                'widget_action' => null,
                'display_order' => 6,
            ],
            [
                'slug' => 'international_transfer',
                'category' => 'transferencia_internacional',
                'label' => 'Transferencia internacional de datos',
                'description' => 'Tus datos pueden ser procesados en servidores ubicados fuera de Chile (ej. Estados Unidos). Nos aseguramos de que los proveedores cumplan con estándares de protección equivalentes a la normativa nacional.',
                'legal_basis' => 'consent',
                'requires_consent' => true,
                'default_value' => false,
                'widget_action' => null,
                'display_order' => 7,
            ],
            [
                'slug' => 'biometric_identification',
                'category' => 'identificacion_biometrica',
                'label' => 'Identificación biométrica',
                'description' => 'Tratamiento de datos biométricos (huellas dactilares, reconocimiento facial) para control de asistencia o acceso a instalaciones. Limitado estrictamente al fin declarado y sin cesión a terceros (Art. 16 ter Ley 21.719).',
                'legal_basis' => 'consent',
                'requires_consent' => true,
                'default_value' => false,
                'widget_action' => null,
                'display_order' => 8,
            ],
            [
                'slug' => 'contractual_execution',
                'category' => 'gestion_general',
                'label' => 'Ejecución del contrato o servicio',
                'description' => 'Tratamiento indispensable para prestarte el servicio contratado o ejecutar medidas precontractuales a tu solicitud. Sin este tratamiento no sería posible entregar el servicio.',
                'legal_basis' => 'contractual',
                'requires_consent' => false,
                'default_value' => true,
                'widget_action' => null,
                'display_order' => 9,
            ],
            [
                'slug' => 'legal_compliance',
                'category' => 'gestion_general',
                'label' => 'Cumplimiento de obligaciones legales',
                'description' => 'Tratamiento de datos estrictamente necesario para cumplir obligaciones impuestas por ley (tributarias, laborales, regulatorias). No requiere consentimiento adicional al contrato o la ley.',
                'legal_basis' => 'legal_obligation',
                'requires_consent' => false,
                'default_value' => true,
                'widget_action' => null,
                'display_order' => 10,
            ],
            [
                'slug' => 'geolocation_tracking',
                'category' => 'geolocalizacion',
                'label' => 'Geolocalización y seguimiento de ubicación',
                'description' => 'Tratamiento de datos de ubicación GPS de vehículos de empresa o dispositivos de trabajo. Solo se activa durante la jornada laboral y con finalidad de coordinación operativa (Art. 16 sexies Ley 21.719).',
                'legal_basis' => 'consent',
                'requires_consent' => true,
                'default_value' => false,
                'widget_action' => null,
                'display_order' => 11,
            ],
            [
                'slug' => 'health_occupational',
                'category' => 'salud_bienestar',
                'label' => 'Salud ocupacional y medicina laboral',
                'description' => 'Tratamiento de datos de salud para medicina preventiva, control de ausentismo o programas de bienestar laboral. Datos accesibles solo por personal médico autorizado.',
                'legal_basis' => 'consent',
                'requires_consent' => true,
                'default_value' => false,
                'widget_action' => null,
                'display_order' => 12,
            ],
        ];

        foreach ($purposes as $purpose) {
            ConsentPurpose::updateOrCreate(
                ['slug' => $purpose['slug']],
                $purpose,
            );
        }
    }
}

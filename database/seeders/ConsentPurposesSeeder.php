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
                'label' => 'Funcionamiento del sitio',
                'description' => 'Permite el funcionamiento básico del sitio: mantener tu sesión activa, recordar el contenido de tu carrito y proteger contra fraudes. No puede desactivarse porque el sitio no funcionaría sin ello.',
                'legal_basis' => 'legitimate_interest',
                'requires_consent' => false,
                'default_value' => true,
                'widget_action' => null,
                'display_order' => 0,
            ],
            [
                'slug' => 'analytics_behavior',
                'category' => 'analisis_comportamiento',
                'label' => 'Estadísticas de visitas',
                'description' => 'Recopilamos información anónima sobre cómo navegas por el sitio —qué páginas visitas, cuánto tiempo permaneces y desde dónde llegas— para mejorar la experiencia. No te identifica personalmente.',
                'legal_basis' => 'consent',
                'requires_consent' => true,
                'default_value' => false,
                'widget_action' => 'load_analytics_scripts',
                'display_order' => 1,
            ],
            [
                'slug' => 'analytics_preferences',
                'category' => 'analisis_comportamiento',
                'label' => 'Recordar tus preferencias',
                'description' => 'Guardamos tu idioma, región y configuración de visualización para que no tengas que volver a elegirlos en cada visita.',
                'legal_basis' => 'consent',
                'requires_consent' => true,
                'default_value' => false,
                'widget_action' => 'load_functional_scripts',
                'display_order' => 2,
            ],
            [
                'slug' => 'marketing_direct',
                'category' => 'comercial_marketing',
                'label' => 'Publicidad y promociones',
                'description' => 'Podemos enviarte anuncios y ofertas sobre productos y servicios que podrían interesarte, tanto en nuestro sitio como en plataformas como Google, Instagram o TikTok. Sin tu permiso, seguirás viendo anuncios pero serán menos relevantes.',
                'legal_basis' => 'consent',
                'requires_consent' => true,
                'default_value' => false,
                'widget_action' => 'load_marketing_scripts',
                'display_order' => 3,
            ],
            [
                'slug' => 'marketing_profiling',
                'category' => 'comercial_marketing',
                'label' => 'Perfilamiento publicitario',
                'description' => 'Construimos un perfil de tus intereses a partir de tu navegación para mostrarte anuncios más relevantes. Tus datos pueden compartirse con plataformas de publicidad como Meta y Google.',
                'legal_basis' => 'consent',
                'requires_consent' => true,
                'default_value' => false,
                'widget_action' => 'consent_mode_v2_ads',
                'display_order' => 4,
            ],
            [
                'slug' => 'functional_third_party',
                'category' => 'analisis_comportamiento',
                'label' => 'Contenido interactivo externo',
                'description' => 'Cargamos servicios de terceros como chats de soporte, videos de YouTube, reproductores de Vimeo y mapas de Google. Sin estas cookies, esos elementos no se mostrarán en el sitio.',
                'legal_basis' => 'consent',
                'requires_consent' => true,
                'default_value' => false,
                'widget_action' => 'load_functional_scripts',
                'display_order' => 5,
            ],
            [
                'slug' => 'service_improvement',
                'category' => 'gestion_general',
                'label' => 'Seguridad y prevención de fraudes',
                'description' => 'Monitoreamos la actividad de la red para detectar intrusiones, prevenir fraudes y garantizar la integridad de los servicios que te ofrecemos. Tienes derecho a oponerte a este tratamiento según la Ley 21.719.',
                'legal_basis' => 'legitimate_interest',
                'requires_consent' => false,
                'default_value' => true,
                'widget_action' => null,
                'display_order' => 6,
            ],
            [
                'slug' => 'international_transfer',
                'category' => 'transferencia_internacional',
                'label' => 'Transferencia de datos al extranjero',
                'description' => 'Tus datos pueden procesarse en servidores ubicados fuera de Chile (principalmente EE.UU.). Exigimos a los proveedores cumplir con estándares de protección equivalentes a la normativa chilena.',
                'legal_basis' => 'consent',
                'requires_consent' => true,
                'default_value' => false,
                'widget_action' => null,
                'display_order' => 7,
            ],
            [
                'slug' => 'biometric_identification',
                'category' => 'identificacion_biometrica',
                'label' => 'Identificación por biometría',
                'description' => 'Usamos tus datos biométricos (huella digital, reconocimiento facial) para identificarte o autenticarte. Limitado estrictamente al fin declarado y sin cesión a terceros (Art. 16 ter Ley 21.719).',
                'legal_basis' => 'consent',
                'requires_consent' => true,
                'default_value' => false,
                'widget_action' => null,
                'display_order' => 8,
            ],
            [
                'slug' => 'contractual_execution',
                'category' => 'gestion_general',
                'label' => 'Prestación del servicio que solicitaste',
                'description' => 'Tratamos tus datos para entregarte el servicio que pediste: gestionar tu consulta o cotización, procesar tu compra o activar tu cuenta. Sin este tratamiento, no podemos atenderte.',
                'legal_basis' => 'contractual',
                'requires_consent' => false,
                'default_value' => true,
                'widget_action' => null,
                'display_order' => 9,
            ],
            [
                'slug' => 'legal_compliance',
                'category' => 'gestion_general',
                'label' => 'Obligaciones legales y tributarias',
                'description' => 'Debemos conservar y tratar ciertos datos para cumplir con leyes chilenas: facturación ante el SII, obligaciones laborales y requerimientos de autoridades competentes.',
                'legal_basis' => 'legal_obligation',
                'requires_consent' => false,
                'default_value' => true,
                'widget_action' => null,
                'display_order' => 10,
            ],
            [
                'slug' => 'geolocation_tracking',
                'category' => 'geolocalizacion',
                'label' => 'Seguimiento de ubicación (GPS)',
                'description' => 'Rastreamos la ubicación GPS de vehículos de empresa o dispositivos de trabajo, solo durante la jornada laboral y con fines de coordinación operativa (Art. 16 sexies Ley 21.719).',
                'legal_basis' => 'consent',
                'requires_consent' => true,
                'default_value' => false,
                'widget_action' => null,
                'display_order' => 11,
            ],
            [
                'slug' => 'health_occupational',
                'category' => 'salud_bienestar',
                'label' => 'Datos de salud laboral',
                'description' => 'Procesamos información de salud para medicina preventiva, control de ausentismo y programas de bienestar laboral. Solo el personal médico autorizado puede acceder a esta información.',
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

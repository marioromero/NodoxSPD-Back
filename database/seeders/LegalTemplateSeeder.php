<?php

namespace Database\Seeders;

use App\Models\LegalTemplate;
use Illuminate\Database\Seeder;

class LegalTemplateSeeder extends Seeder
{
    public function run(): void
    {
        // ==========================================
        // 1. POLÍTICA DE PRIVACIDAD WEB
        // ==========================================
        $webPrivacyContent = <<<'BLADE_WEB'
<div class="policy-header">
    <h1>POLÍTICA DE PRIVACIDAD Y TRATAMIENTO DE DATOS PERSONALES</h1>
    <p><strong>Responsable del Tratamiento:</strong> {{ $company->business_name }} (en adelante, el "Responsable").</p>
    <p><strong>RUT:</strong> {{ $company->tax_id }}</p>
    <p><strong>Domicilio:</strong> {{ $company->legal_address }}</p>
    <p><strong>Delegado de Protección de Datos (DPO):</strong> {{ $company->dpo_contact['name'] ?? 'Atención directa por Responsable Legal' }}</p>
    <p><strong>Canal de Contacto ARCO-P:</strong> {{ $company->arco_contact_email }}</p>
    <p><strong>Versión:</strong> {{ $policy->company_version }} | <strong>Fecha de Publicación:</strong> {{ $policy->published_at ? $policy->published_at->format('d/m/Y') : 'Borrador' }}</p>
</div>

<p>La presente política describe cómo el Responsable recolecta y trata sus datos personales conforme a los principios de licitud, lealtad, transparencia y proporcionalidad establecidos en la Ley 21.719 de la República de Chile.</p>

<hr>

<h2>1. Finalidades y Categorías de Datos Tratados</h2>
<p>El Responsable trata los datos estrictamente necesarios para el cumplimiento de sus funciones operativas y comerciales, bajo las siguientes finalidades:</p>

<ul>
@if(in_array('informativa', $wizard_data['step_1_website_functions']))
    <li>
        <strong>1.1. Gestión de Consultas y Contacto:</strong> El Responsable tratará datos de identificación y contacto (como nombre, correo electrónico y teléfono) con la finalidad exclusiva de gestionar sus solicitudes de información, consultas o cotizaciones. <br>
        <em>Base de Licitud:</em> Este tratamiento es lícito al ser necesario para la ejecución de medidas precontractuales adoptadas a solicitud del titular, según el Art. 13, letra c de la Ley.
    </li>
@endif

@if(in_array('ecommerce', $wizard_data['step_1_website_functions']))
    <li>
        <strong>1.2. Gestión de Ventas y Transacciones:</strong> El Responsable tratará datos de identificación, contacto, información de despacho y datos relativos a obligaciones económicas, financieras y comerciales para procesar sus órdenes de compra, gestionar pagos online y efectuar la entrega de productos o servicios. <br>
        <em>Base de Licitud:</em> El tratamiento es necesario para la ejecución del contrato de compraventa suscrito con el titular (Art. 13, letra c) y para el cumplimiento de obligaciones legales de carácter tributario y comercial (Art. 13, letra b).
    </li>
@endif

@if(in_array('saas', $wizard_data['step_1_website_functions']))
    <li>
        <strong>1.3. Gestión de Plataforma y Cuentas de Usuario:</strong> El Responsable tratará credenciales de acceso (usuario/contraseña), datos de perfil, logs de actividad y cookies técnicas necesarias para permitir el registro, la autenticación y la correcta prestación de las funcionalidades de la plataforma. <br>
        <em>Base de Licitud:</em> Este tratamiento se fundamenta en la ejecución del contrato de prestación de servicios digitales o términos de uso aceptados por el titular (Art. 13, letra c).
    </li>
@endif
</ul>
@if(!in_array('ninguna', $wizard_data['step_2_sensitive_data']) && count($wizard_data['step_2_sensitive_data']) > 0)

@php
    $sensitive_map = [
        'salud' => 'Datos de Salud (Fichas clínicas, recetas, diagnósticos o similares)',
        'biometria' => 'Datos Biométricos (Huella digital, reconocimiento facial o voz)',
        'politica' => 'Afiliación Política',
        'sindical' => 'Afiliación Sindical o Gremial',
        'religion' => 'Creencias religiosas',
        'sexual' => 'Orientación sexual',
        'racial' => 'Origen racial o étnico'
    ];

    $selected_sensitive = [];
    foreach($wizard_data['step_2_sensitive_data'] as $item) {
        if(isset($sensitive_map[$item])) {
            $selected_sensitive[] = $sensitive_map[$item];
        } elseif ($item === 'otros' && !empty($wizard_data['step_2_sensitive_data_other'])) {
            $selected_sensitive[] = htmlspecialchars($wizard_data['step_2_sensitive_data_other']);
        }
    }

    // Lógica para determinar la base legal principal
    $health_medical = in_array('salud', $wizard_data['step_2_sensitive_data']) && isset($wizard_data['step_2_health_basis']) && $wizard_data['step_2_health_basis'] === 'medical_care';

    $group_internal = (in_array('politica', $wizard_data['step_2_sensitive_data']) || in_array('sindical', $wizard_data['step_2_sensitive_data']) || in_array('religion', $wizard_data['step_2_sensitive_data'])) && isset($wizard_data['step_2_group_basis']) && $wizard_data['step_2_group_basis'] === 'internal_members';
@endphp

<h2>2. Tratamiento de Categorías Especiales de Datos (Datos Sensibles)</h2>
<p>El Responsable informa que, para la prestación de sus servicios, realiza el tratamiento de las siguientes categorías de datos sensibles: <strong>{{ implode(', ', $selected_sensitive) }}</strong>.</p>

<strong>2.1. Base de Licitud para Datos Sensibles:</strong>
@if($health_medical)
    <p>Respecto a los <strong>Datos de Salud</strong>, su tratamiento se fundamenta en la necesidad para la prevención, diagnóstico y prestación de asistencia sanitaria o tratamientos médicos, de conformidad con el Artículo 16 letra b) de la Ley 21.719 y la normativa sanitaria vigente, estando sujeto al estricto deber de secreto profesional.</p>
@endif

@if($group_internal)
    <p>Respecto a los datos de <strong>Afiliación o Creencias</strong>, su tratamiento es realizado en el ámbito de las actividades legítimas del Responsable (en su calidad de fundación, asociación o gremio) y se refiere exclusivamente a sus miembros actuales o antiguos, conforme al Artículo 16 letra e) de la Ley 21.719.</p>
@endif

@if(!$health_medical && !$group_internal)
    <p>Para el tratamiento de la información sensible recopilada, nuestra base de licitud es su <strong>Consentimiento Expreso, Específico e Informado</strong> (Artículo 16 de la Ley 21.719). Este no se presume ni se obtiene de forma tácita; se recaba mediante una declaración escrita o un medio tecnológico que permite acreditar fehacientemente su voluntad.</p>
@elseif(($health_medical || $group_internal) && count(array_intersect(['biometria', 'sexual', 'racial', 'otros'], $wizard_data['step_2_sensitive_data'])) > 0)
    <p>Para las demás categorías de datos sensibles recopiladas que no entran en las excepciones legales mencionadas anteriormente, nuestra base de licitud es su <strong>Consentimiento Expreso, Específico e Informado</strong> (Artículo 16 de la Ley).</p>
@endif

<p><strong>2.2. Medidas de Seguridad Especiales:</strong> Debido a la naturaleza íntima de estos datos, {{ $company->business_name }} aplica estándares de seguridad superiores, que incluyen:</p>
<ul>
    <li><strong>Cifrado de Extremo a Extremo:</strong> Los datos sensibles se almacenan de forma cifrada para evitar el acceso no autorizado.</li>
    <li><strong>Control de Acceso Restringido:</strong> Solo el personal estrictamente autorizado y sujeto a un deber de secreto y confidencialidad permanente puede acceder a esta información.</li>
    <li><strong>Seudonimización:</strong> Siempre que sea técnicamente factible, se desvinculará el dato sensible de su identidad directa para minimizar riesgos de filtración.</li>
</ul>

@if(in_array('biometria', $wizard_data['step_2_sensitive_data']))
<p><strong>2.3. Tratamiento de Datos Biométricos:</strong> Conforme al Artículo 16 ter de la Ley, le informamos que el sistema biométrico utilizado tiene como finalidad exclusiva la identificación o autenticación del titular. Sus plantillas biométricas son almacenadas de forma cifrada y no serán utilizadas para deducir otra información sensible ni para realizar perfilamiento sin su autorización legal adicional.</p>
@endif

@endif
<hr>

@if(!($wizard_data['step_3_minors']['active'] ?? false))
    <h2>3. Privacidad de Menores de Edad</h2>
    <p>Los servicios y el sitio web de {{ $company->business_name }} están dirigidos exclusivamente a personas mayores de 18 años. El Responsable no recopila ni solicita intencionadamente datos personales de menores de edad. En caso de que se tome conocimiento de la existencia de datos de un menor en nuestras bases de datos sin la debida autorización de su representante legal, se procederá a su eliminación inmediata de acuerdo con el Principio de Calidad y el Art. 16 quáter de la Ley 21.719.</p>
@else
    @php
        $minors_purposes_map = [
            'servicio' => 'Prestación del servicio principal (educación, salud o plataforma dedicada)',
            'seguridad' => 'Control de acceso y seguridad de las instalaciones',
            'legal' => 'Cumplimiento de obligaciones legales ante autoridades',
            'marketing' => 'Marketing, fidelización y clubes infantiles'
        ];

        $selected_minors_purposes = [];
        foreach(($wizard_data['step_3_minors']['purposes'] ?? []) as $p) {
            if(isset($minors_purposes_map[$p])) {
                $selected_minors_purposes[] = $minors_purposes_map[$p];
            } elseif ($p === 'otros' && !empty($wizard_data['step_3_minors']['other_purpose'])) {
                $selected_minors_purposes[] = htmlspecialchars($wizard_data['step_3_minors']['other_purpose']);
            }
        }

        // Lógica cruzada: Datos Sensibles + Menores
        $has_sensitive = !in_array('ninguna', $wizard_data['step_2_sensitive_data'] ?? ['ninguna']);
    @endphp

    <h2>3. Tratamiento de Datos de Niños, Niñas y Adolescentes (NNA)</h2>
    <p>En cumplimiento del Art. 16 quáter de la Ley 21.719, {{ $company->business_name }} realiza el tratamiento de datos de menores de edad bajo el estricto respeto a su interés superior y su autonomía progresiva.</p>

    <p><strong>3.1. Finalidad del Tratamiento:</strong> Los datos de NNA son tratados exclusivamente para las siguientes finalidades: <strong>{{ implode(', ', $selected_minors_purposes) }}</strong>.</p>

    <p><strong>3.2. Consentimiento y Validación:</strong></p>
    <ul>
        <li><strong>Niños y Niñas (menores de 14 años):</strong> El tratamiento solo se realiza contando con la autorización previa de sus padres o representantes legales, verificada mediante: <em>{{ $wizard_data['step_3_minors']['verification_method'] ?? 'mecanismo de verificación formal' }}</em>.</li>
        <li><strong>Adolescentes (14 a 18 años):</strong> Podrán autorizar por sí mismos el tratamiento de sus datos, excepto cuando se trate de datos sensibles de menores de 16 años, en cuyo caso se requerirá obligatoriamente el consentimiento del padre o tutor.</li>
    </ul>

    <p><strong>3.3. Obligación de Resguardo:</strong> El Responsable asume la obligación especial de velar por el uso lícito y la protección reforzada de esta información, limitando su acceso solo a personal autorizado.</p>

    {{-- Cabo suelto 1: Marketing con menores --}}
    @if(in_array('marketing', $wizard_data['step_3_minors']['purposes'] ?? []))
        <div class="legal-warning">
            <p><strong>Aviso Especial sobre Perfilamiento:</strong> Se prohíbe la elaboración de perfiles con fines comerciales sobre datos de NNA sin que medie un análisis de impacto que garantice que no se afecta su desarrollo integral.</p>
        </div>
    @endif

    {{-- Cabo suelto 2: Datos Sensibles + Menores (Principio de Seguridad Máxima) --}}
    @if($has_sensitive)
        <div class="legal-warning">
            <p><strong>Evaluación de Impacto (PIA):</strong> Debido a que el tratamiento incluye datos sensibles de menores de edad, el Responsable declara haber realizado o estar en proceso de validación de una Evaluación de Impacto relativa a la Protección de Datos (Art. 15 ter), garantizando medidas de cifrado y seguridad de alto estándar.</p>
        </div>
    @endif
@endif
<hr>

@php
    $providers_map = [
        'google_analytics' => 'Google LLC (EE.UU.) - Analytics/Ads',
        'meta' => 'Meta Platforms, Inc. (EE.UU.) - Pixel/Ads',
        'shopify' => 'Shopify Inc. (Canadá) - E-commerce',
        'wix' => 'Wix.com Ltd. (Israel) - Plataforma',
        'mailchimp' => 'The Rocket Science Group LLC (EE.UU.) - Mailchimp',
        'hubspot' => 'HubSpot, Inc. (EE.UU.) - CRM',
        'aws' => 'Amazon Web Services, Inc. (EE.UU.) - Cloud Hosting',
        'azure' => 'Microsoft Corporation (EE.UU.) - Azure Cloud',
        'google_cloud' => 'Google LLC (EE.UU.) - Cloud Hosting'
    ];

    $selected_providers = [];
    $has_local = in_array('local', $wizard_data['step_4_providers'] ?? []);

    // Filtrar los que no son locales para ver si hay extranjeros
    $foreign_keys = array_diff($wizard_data['step_4_providers'] ?? [], ['local']);

    foreach($foreign_keys as $key) {
        if(isset($providers_map[$key])) {
            $selected_providers[] = $providers_map[$key];
        }
    }

    if(!empty($wizard_data['step_4_other_provider'])) {
        $selected_providers[] = htmlspecialchars($wizard_data['step_4_other_provider']);
    }

    $has_foreign = count($selected_providers) > 0;
@endphp

@if($has_local && !$has_foreign)
    {{-- CASO A: 100% LOCAL --}}
    <h2>4. Almacenamiento y Proveedores de Infraestructura</h2>
    <p>{{ $company->business_name }} informa que los datos personales recolectados a través de sus plataformas son almacenados y tratados exclusivamente en servidores e infraestructuras ubicadas dentro del territorio nacional de la República de Chile. Por consiguiente, no se realizan operaciones de transferencia internacional de datos personales hacia terceros países.</p>

@elseif(!$has_local && $has_foreign)
    {{-- CASO B: 100% EXTRANJERO --}}
    <h2>4. Transferencia Internacional de Datos y Ecosistema Tecnológico</h2>
    <p>Para garantizar la operatividad técnica, analítica y comercial de nuestra plataforma, {{ $company->business_name }} aloja su infraestructura y utiliza herramientas proporcionadas por proveedores extranjeros. En consecuencia, sus datos son transferidos fuera del territorio nacional.</p>

@elseif($has_local && $has_foreign)
    {{-- CASO C: HÍBRIDO (El escenario más común y más preciso) --}}
    <h2>4. Almacenamiento Local y Transferencias Internacionales Parciales</h2>
    <p>{{ $company->business_name }} almacena su base de datos principal en servidores ubicados en Chile. No obstante, para optimizar nuestros servicios, analítica y comunicaciones, utilizamos herramientas tecnológicas de terceros que requieren la transferencia internacional de ciertas categorías de datos.</p>
@endif

@if($has_foreign)
    <p><strong>4.1. Destinatarios Internacionales:</strong> Sus datos fluyen de manera segura hacia los siguientes proveedores que actúan en calidad de mandatarios del tratamiento:</p>
    <ul>
        @foreach($selected_providers as $provider)
            <li>{{ $provider }}</li>
        @endforeach
    </ul>

    <p><strong>4.2. Garantías Legales y Contractuales:</strong> De conformidad con el Art. 14 ter, letra h, y los Artículos 27 y 28 de la Ley 21.719, estas transferencias internacionales son lícitas fundamentándose en:</p>
    <ul>
        <li><strong>Cláusulas Contractuales Tipo y Adecuación:</strong> Los destinatarios se encuentran sujetos a contratos que establecen garantías adecuadas de seguridad, o se ubican en jurisdicciones reconocidas por la Agencia como garantes de un nivel de protección adecuado.</li>
        <li><strong>Mandato de Tratamiento (Art. 15 bis):</strong> Los proveedores mencionados actúan bajo nuestras instrucciones estrictas y documentadas. Tienen prohibición absoluta de utilizar sus datos personales para fines propios o distintos a los estrictamente convenidos para la prestación del servicio. En caso de incumplimiento, {{ $company->business_name }} ejercerá las acciones legales correspondientes para proteger la información de sus usuarios.</li>
    </ul>
@endif
<hr>

@php
    $has_ai = $wizard_data['step_5_ai']['active'] ?? false;
    // Detección cruzada: ¿Hacen comercio o marketing? (Basado en el Paso 1 y 3)
    $has_marketing_context = in_array('ecommerce', $wizard_data['step_1_website_functions'] ?? []) || in_array('marketing', $wizard_data['step_3_minors']['purposes'] ?? []);
@endphp

@if(!$has_ai)
    {{-- CASO A: SIN DECISIONES AUTOMATIZADAS --}}
    <h2>5. Decisiones Automatizadas y Elaboración de Perfiles</h2>
    <p>El Responsable informa que, a través de sus plataformas digitales, <strong>no se realizan</strong> tratamientos de datos destinados a adoptar decisiones basadas únicamente en el procesamiento automatizado de su información, ni se realizan procesos de elaboración de perfiles que produzcan efectos jurídicos o le afecten de manera significativa.</p>
@else
    {{-- CASO B: CON DECISIONES AUTOMATIZADAS (IA / SCORING) --}}
    <h2>5. Tratamiento Automatizado y Elaboración de Perfiles</h2>
    <p>En cumplimiento del Art. 14 ter, letra l de la Ley 21.719, {{ $company->business_name }} informa que utiliza sistemas tecnológicos para la toma de decisiones automatizadas y/o elaboración de perfiles de acuerdo con lo siguiente:</p>

    <p><strong>5.1. Lógica del Tratamiento:</strong> El sistema utiliza algoritmos que procesan las siguientes categorías de datos: <em>{{ $wizard_data['step_5_ai']['parameters'] ?? 'datos de comportamiento y transaccionales' }}</em>. La lógica matemática o automatizada aplicada consiste en: <em>{{ $wizard_data['step_5_ai']['logic'] ?? 'análisis y evaluación de perfil' }}</em>.</p>

    <p><strong>5.2. Consecuencias para el Titular:</strong> El resultado de este tratamiento automatizado tiene como consecuencia directa: <em>{{ $wizard_data['step_5_ai']['consequences'] ?? 'la personalización del servicio entregado' }}</em>.</p>

    <p><strong>5.3. Derechos ARCO-P y Garantías Algorítmicas:</strong> De conformidad con el Art. 8 bis de la Ley, usted tiene derecho a no ser objeto de decisiones basadas únicamente en el tratamiento automatizado de sus datos que produzcan efectos jurídicos sobre usted. En consecuencia, podrá ejercer en cualquier momento ante <strong>{{ $company->arco_contact_email }}</strong> los siguientes derechos:</p>
    <ul>
        <li><strong>Derecho a Explicación:</strong> Solicitar información detallada y comprensible sobre la lógica aplicada en la decisión que le afectó.</li>
        <li><strong>Impugnación e Intervención Humana:</strong> Expresar su punto de vista, solicitar que un operador humano (y no una máquina) revise la decisión adoptada por el algoritmo, e impugnar el resultado si lo considera injusto o erróneo.</li>
        <li><strong>Oposición al Perfilamiento:</strong> Oponerse a que sus datos se utilicen para elaborar perfiles predictivos.
        @if($has_marketing_context)
            <em>(Nota: Dado que nuestros servicios incluyen fines de mercadotecnia directa, su derecho a oponerse a este perfilamiento comercial es absoluto y procederemos a cesarlo inmediatamente tras su solicitud).</em>
        @endif
        </li>
    </ul>

    <div class="legal-warning">
        <p><strong>Declaración de Cumplimiento (Art. 15 ter):</strong> El Responsable declara someter estos sistemas automatizados a Evaluaciones de Impacto en la Protección de Datos (PIA) periódicas, garantizando la mitigación de sesgos discriminatorios y la protección de los derechos fundamentales de los titulares.</p>
    </div>
@endif
<hr>

<h2>6. Plazos de Conservación de los Datos</h2>
<p>En virtud del Principio de Proporcionalidad, {{ $company->business_name }} conservará sus datos personales únicamente por el tiempo estrictamente necesario para cumplir con las finalidades descritas en esta política, tras lo cual serán eliminados o anonimizados (perdiendo su calidad de dato personal), salvo que exista una obligación legal o contractual que exija su retención por un periodo superior.</p>

<ul>
    @if($wizard_data['step_6_retention']['tax_commercial'] ?? false)
        <li><strong>Obligaciones Legales y Comerciales:</strong> Los datos vinculados a transacciones, facturación y registros contables se conservarán por un plazo mínimo de 6 años, de acuerdo con las normativas vigentes del Servicio de Impuestos Internos (SII) y la legislación mercantil chilena. Este plazo primará sobre cualquier solicitud de eliminación anticipada.</li>
    @endif

    @if($wizard_data['step_6_retention']['user_accounts'] ?? false)
        <li><strong>Gestión de Cuentas de Usuario:</strong> Los datos de su perfil y registro se mantendrán vigentes mientras usted mantenga su cuenta activa en nuestra plataforma. Una vez solicitada la eliminación de la cuenta, procederemos a la supresión o anonimización de la información en un plazo máximo de <em>{{ $wizard_data['step_6_retention']['account_days'] ?? '30' }}</em> días, siempre que no existan procesos judiciales o reclamaciones pendientes.</li>
    @endif

    @if($wizard_data['step_6_retention']['marketing'] ?? false)
        <li><strong>Finalidades de Marketing y Contacto:</strong> Los datos utilizados para el envío de comunicaciones comerciales y publicidad se conservarán de manera indefinida hasta que usted ejerza su Derecho de Cancelación/Oposición o retire su consentimiento a través de los canales habilitados.</li>
    @endif

    @if($wizard_data['step_6_retention']['custom'] ?? false && !empty($wizard_data['step_6_retention']['custom_period']))
        <li><strong>Plazo de Interacción Específica:</strong> Sus datos serán conservados por un periodo máximo de <em>{{ htmlspecialchars($wizard_data['step_6_retention']['custom_period']) }}</em>, contado desde su última interacción efectiva con nuestros servicios, con el fin de garantizar la continuidad de la atención solicitada.</li>
    @endif
</ul>

<hr>

<div class="policy-footer">
    <h2>Aceptación y Cambios a la Política</h2>
    <p>El titular declara haber sido informado de las condiciones de tratamiento de sus datos y otorga su consentimiento expreso en los términos aquí expresados cuando dicha base de licitud sea aplicable. {{ $company->business_name }} se reserva el derecho de modificar esta política para adaptarla a novedades legislativas, jurisprudenciales o de la Agencia de Protección de Datos Personales, lo cual será debidamente notificado a través de esta plataforma.</p>

    <div class="legal-signatures">
        <p><strong>Responsable Titular:</strong> {{ $company->legal_representative_name }}</p>
        <p><strong>Cargo:</strong> Representante Legal</p>
        <p><strong>RUT Representante:</strong> {{ $company->legal_representative_tax_id }}</p>
        <br>
        <p><strong>Versión del Documento:</strong> {{ $policy->company_version }}</p>
        <p><strong>Fecha de Última Actualización:</strong> {{ $policy->published_at ? $policy->published_at->format('d/m/Y') : 'Borrador' }}</p>
    </div>
</div>

BLADE_WEB;

        LegalTemplate::updateOrCreate(
            ['document_type' => 'privacy_policy', 'version' => 1],
            [
                'name' => 'Política de Privacidad Web y Tratamiento de Datos',
                'content' => trim($webPrivacyContent),
                'wizard_schema' => [
                    'steps' => ['Funciones de la plataforma', 'Datos sensibles', 'Menores de edad', 'Proveedores y terceros', 'Inteligencia artificial', 'Plazos de retención'],
                ],
                'is_active' => true,
            ]
        );

        // ==========================================
        // 2. POLÍTICA DE COOKIES
        // ==========================================
        $cookiePolicyContent = <<<'BLADE_COOKIE'
<div class="policy-header">
    <h1>POLÍTICA DE COOKIES Y TECNOLOGÍAS DE RASTREO</h1>
    <p><strong>Responsable del Tratamiento:</strong> {{ $company->business_name }}</p>
    <p><strong>RUT:</strong> {{ $company->tax_id }}</p>
    <p><strong>Canal de Contacto y Derechos ARCO-P:</strong> {{ $company->arco_contact_email }}</p>
    <p><strong>Versión:</strong> {{ $policy->company_version }} | <strong>Fecha de Última Actualización:</strong> {{ $policy->published_at ? $policy->published_at->format('d/m/Y') : 'Borrador' }}</p>
</div>

<p>La presente política tiene como objetivo informar de manera clara, transparente y accesible sobre el uso de cookies, píxeles de seguimiento y tecnologías similares en las plataformas digitales de <strong>{{ $company->business_name }}</strong>, en cumplimiento del deber de información establecido en el Artículo 14 ter de la Ley 21.719 de la República de Chile.</p>

<hr>

<h2>1. Cookies Técnicas y Estrictamente Necesarias</h2>
<p>Nuestra plataforma utiliza cookies técnicas que son indispensables para el funcionamiento estructural, la navegación y la seguridad básica del sitio web. Estas tecnologías permiten funciones críticas tales como la distribución del tráfico web (balanceo de carga), el mantenimiento de la sesión de usuario autenticado, la prevención de fraudes y, cuando corresponda, la operatividad del carrito de compras.</p>

<ul>
    <li><strong>1.1. Base de Licitud (Exención de Consentimiento):</strong> Conforme al Artículo 13, letra c) de la Ley 21.719, la instalación de estas cookies <strong>no requiere de su consentimiento previo</strong>. Su tratamiento es estrictamente necesario para la ejecución del servicio o la funcionalidad específica solicitada explícitamente por usted al acceder a nuestra web.</li>

    <li><strong>1.2. Alcance y Proporcionalidad:</strong> En estricto respeto al Principio de Proporcionalidad (Art. 3, letra c), estas cookies no recolectan información para fines comerciales, no rastrean su actividad en sitios web de terceros, ni son utilizadas para la elaboración de perfiles de usuario.</li>
</ul>

<div class="legal-warning" style="margin-top: 10px; font-size: 0.9em; background: #fdfdfe; border-left: 3px solid #6c757d; padding: 10px;">
    <strong>Aviso sobre Desactivación:</strong> El usuario tiene la libertad de configurar su navegador para bloquear o ser alertado sobre la presencia de estas cookies técnicas. Sin embargo, advertimos que su bloqueo forzado a nivel de navegador impedirá el funcionamiento de áreas o características vitales del sitio web.
</div>
@if($wizard_data['step_2_analytics']['active'] ?? false)

@php
    $analytics_map = [
        'google_analytics' => 'Google Analytics (Google LLC, EE.UU.)',
        'hotjar' => 'Hotjar (Hotjar Ltd, Malta)',
        'mixpanel' => 'Mixpanel (Mixpanel Inc., EE.UU.)',
        'clarity' => 'Microsoft Clarity (Microsoft Corp., EE.UU.)',
        'matomo' => 'Matomo (InnoCraft, Nueva Zelanda)'
    ];

    $selected_analytics = [];
    $has_foreign_analytics = false;

    foreach($wizard_data['step_2_analytics']['providers'] ?? [] as $provider) {
        if(isset($analytics_map[$provider])) {
            $selected_analytics[] = $analytics_map[$provider];
            $has_foreign_analytics = true; // La mayoría de analíticas son extranjeras
        }
    }

    if(!empty($wizard_data['step_2_analytics']['other_provider'])) {
        $selected_analytics[] = htmlspecialchars($wizard_data['step_2_analytics']['other_provider']);
        $has_foreign_analytics = true; // Asumimos transferencia por defecto para seguridad
    }
@endphp

<hr>

<h2>2. Cookies de Análisis o Medición</h2>
<p>Con su <strong>consentimiento previo y explícito</strong>, recabado a través de nuestro Panel de Preferencias (Banner de Cookies), el sitio web de <strong>{{ $company->business_name }}</strong> utiliza cookies analíticas para cuantificar el número de usuarios y realizar la medición y análisis estadístico de la utilización que hacen los usuarios de nuestro servicio.</p>

<ul>
    <li><strong>2.1. Finalidad y Proporcionalidad:</strong> Estos datos se tratan con el fin exclusivo de introducir mejoras en la oferta de productos, servicios y diseño de la interfaz tras analizar los hábitos de navegación. Esta información se procesa de forma agregada y no se utiliza para identificar personalmente al usuario fuera del ámbito estadístico.</li>

    <li><strong>2.2. Proveedores de Analítica (Mandatarios):</strong> La gestión técnica de estas cookies se realiza a través de los siguientes proveedores externos, quienes actúan bajo instrucciones estrictas en calidad de mandatarios del tratamiento (Art. 15 bis):
        <ul>
            @foreach($selected_analytics as $provider)
                <li>{{ $provider }}</li>
            @endforeach
        </ul>
    </li>

    @if($has_foreign_analytics)
    <li><strong>2.3. Transferencia Internacional y Seudonimización:</strong> Se informa que el uso de estas herramientas implica la comunicación de datos telemáticos a proveedores en el extranjero. {{ $company->business_name }} garantiza que estas transferencias se amparan en cláusulas contractuales tipo (Art. 27). Adicionalmente, se han implementado medidas de privacidad por diseño, tales como la <strong>anonimización/truncamiento de la dirección IP</strong> del usuario antes de su almacenamiento en servidores extranjeros, minimizando cualquier riesgo de reidentificación.</li>
    @endif
</ul>

@endif
@if($wizard_data['step_3_marketing']['active'] ?? false)

@php
    $marketing_map = [
        'meta_pixel' => 'Meta Platforms, Inc. (Pixel de Facebook/Instagram)',
        'google_ads' => 'Google LLC (Google Ads y Remarketing)',
        'tiktok_pixel' => 'TikTok Inc. (TikTok Ads)',
        'linkedin_insight' => 'LinkedIn Corporation (Insight Tag)',
        'twitter_pixel' => 'X Corp. (Twitter Ads)'
    ];

    $selected_marketing = [];

    foreach($wizard_data['step_3_marketing']['providers'] ?? [] as $provider) {
        if(isset($marketing_map[$provider])) {
            $selected_marketing[] = $marketing_map[$provider];
        }
    }

    if(!empty($wizard_data['step_3_marketing']['other_provider'])) {
        $selected_marketing[] = htmlspecialchars($wizard_data['step_3_marketing']['other_provider']);
    }
@endphp

<hr>

<h2>3. Cookies de Publicidad Comportamental y Perfilamiento</h2>
<p>Con su <strong>autorización expresa</strong>, el sitio web de <strong>{{ $company->business_name }}</strong> utiliza cookies de rastreo y publicidad gestionadas por terceros. Estas tecnologías nos permiten adaptar nuestra oferta a sus intereses.</p>

<ul>
    <li><strong>3.1. Finalidad y Elaboración de Perfiles:</strong> Estas cookies recopilan información sobre sus hábitos de navegación, tiempo de permanencia e interacciones para crear un perfil de sus intereses (Art. 8 bis de la Ley 21.719). Esto nos permite mostrarle anuncios relevantes en plataformas de terceros y medir la eficacia de nuestras campañas publicitarias.<br>
    <em>Terceros autorizados (Mandatarios):</em> <strong>{{ implode(', ', $selected_marketing) }}</strong>.</li>

    <li><strong>3.2. Transferencia Internacional de Datos:</strong> Se informa expresamente al titular que el uso de estas herramientas implica la comunicación de identificadores en línea a los proveedores mencionados, cuyos servidores se encuentran fuera del territorio nacional. Estas operaciones se realizan bajo el amparo de cláusulas contractuales tipo u otros mecanismos que garantizan un nivel adecuado de protección (Artículos 27 y 28).</li>

    <li><strong>3.3. Control y Revocación Absoluta:</strong> Usted puede retirar su consentimiento para este tratamiento específico en cualquier momento a través de nuestro centro de preferencias de privacidad o modificando la configuración de su navegador, sin que ello afecte la licitud de la navegación básica del sitio o la prestación del servicio.</li>
</ul>

<div class="legal-warning" style="margin-top: 10px; font-size: 0.9em; background: #fdfdfe; border-left: 3px solid #dc3545; padding: 10px;">
    <strong>Declaración de Impacto y Límites del Perfilamiento (Art. 14 ter / Art. 15 ter):</strong> El Responsable declara formalmente que la elaboración de perfiles descrita en esta sección tiene fines exclusivamente publicitarios y comerciales genéricos. En ningún caso estos algoritmos toman decisiones automatizadas que produzcan efectos jurídicos sobre usted, ni son utilizados para la discriminación arbitraria de precios, denegación de servicios u otras consecuencias que afecten significativamente sus derechos fundamentales.
</div>

@endif
@if($wizard_data['step_4_functionality']['active'] ?? false)

@php
    $functionality_map = [
        'youtube' => 'Reproductores Multimedia (YouTube/Vimeo)',
        'maps' => 'Mapas Interactivos (Google Maps/Mapbox)',
        'whatsapp' => 'Widgets de Chat (WhatsApp/Intercom/Zendesk)',
        'social' => 'Plugins y Botones Sociales (Facebook, Twitter, LinkedIn)',
        'fonts' => 'Librerías de Fuentes Externas (Google Fonts)'
    ];

    $selected_functionality = [];

    foreach($wizard_data['step_4_functionality']['providers'] ?? [] as $provider) {
        if(isset($functionality_map[$provider])) {
            $selected_functionality[] = $functionality_map[$provider];
        }
    }

    if(!empty($wizard_data['step_4_functionality']['other_provider'])) {
        $selected_functionality[] = htmlspecialchars($wizard_data['step_4_functionality']['other_provider']);
    }
@endphp

<hr>

<h2>4. Cookies de Personalización y Funcionalidad de Terceros</h2>
<p>Para ofrecerle servicios interactivos, soporte en línea y contenido multimedia enriquecido, el sitio web de <strong>{{ $company->business_name }}</strong> tiene integradas herramientas y *widgets* de terceros que pueden instalar cookies u otros rastreadores en su dispositivo.</p>

<ul>
    <li><strong>4.1. Servicios Integrados:</strong> Se informa a los usuarios el uso de las siguientes funcionalidades externas: <strong>{{ implode(', ', $selected_functionality) }}</strong>.</li>

    <li><strong>4.2. Tratamiento por Terceros y Corresponsabilidad:</strong> Estos servicios son proporcionados por entidades independientes, quienes actúan bajo sus propias políticas de privacidad. Al interactuar con estos widgets (como reproducir un video o utilizar un botón de compartir), dichos terceros pueden rastrear su actividad de navegación. {{ $company->business_name }} informa que no tiene control directo sobre la información recopilada de manera autónoma por estas entidades una vez que usted interactúa con sus componentes.</li>

    <li><strong>4.3. Transferencia Internacional (Art. 14 ter, letra h):</strong> Se advierte expresamente que el uso de estas herramientas implica la transferencia internacional de sus datos técnicos (como su dirección IP) a los países de origen de estos proveedores (principalmente EE.UU.). Estas operaciones operan bajo las salvaguardas legales que estos últimos declaran en sus términos de servicio corporativos.</li>
</ul>

<div class="legal-warning" style="margin-top: 10px; font-size: 0.9em; background: #e8f4f8; border-left: 3px solid #17a2b8; padding: 10px;">
    <strong>Nota de Privacidad por Diseño (Art. 14 quáter):</strong> En cumplimiento del deber de protección desde el diseño, el Responsable declara implementar mecanismos tecnológicos que bloquean la carga y ejecución de estos *widgets* de terceros hasta que usted otorgue su consentimiento expreso mediante nuestro Panel de Preferencias, garantizando que ninguna cookie funcional externa sea instalada por defecto al cargar el sitio web.
</div>

@endif
<hr>

<h2>5. Gestión, Configuración y Revocación del Consentimiento</h2>
<p>De conformidad con el Artículo 12 de la Ley 21.719, usted tiene el derecho inalienable de retirar su consentimiento para el uso de cookies no esenciales en cualquier momento, de forma gratuita y utilizando medios equivalentes a los empleados para su otorgamiento.</p>

<ul>
    <li><strong>5.1. Centro de Preferencias de Privacidad:</strong> Para facilitar este control permanente, <strong>{{ $company->business_name }}</strong> pone a su disposición nuestro Panel de Preferencias. Puede acceder a él en cualquier momento haciendo clic en el siguiente enlace para modificar sus opciones o revocar permisos previos:<br>
     <a href="#" class="open-privacy-widget" onclick="event.preventDefault(); window.dispatchEvent(new Event('openPrivacyWidget'));"><strong>Abrir Centro de Preferencias de Cookies</strong></a></li>

    <li><strong>5.2. Efectos de la Revocación (No Retroactividad):</strong> La retirada de su consentimiento no afectará a la licitud de los tratamientos basados en el consentimiento previos a dicha retirada. Una vez revocado, nuestros sistemas bloquearán de forma inmediata la instalación y lectura de las cookies asociadas a dicha categoría en su dispositivo.</li>

    <li><strong>5.3. Configuración del Navegador:</strong> Adicionalmente, le informamos que puede bloquear, restringir o eliminar las cookies instaladas en su equipo mediante la configuración de las opciones de seguridad del navegador que utilice (Google Chrome, Mozilla Firefox, Safari, Microsoft Edge, etc.). No obstante, le recordamos que el bloqueo forzado de todas las cookies (incluidas las estrictamente técnicas) afectará la funcionalidad y estabilidad de nuestro sitio web.</li>
</ul>

<hr>

<div class="policy-footer">
    <h2>Aceptación y Control de Versiones</h2>
    <p>Al utilizar este sitio web y gestionar sus preferencias a través de nuestro widget tecnológico, usted declara haber sido informado de manera clara y comprensible sobre el uso de tecnologías de rastreo, de acuerdo con los estándares de transparencia de la Agencia de Protección de Datos Personales.</p>

    <div class="legal-signatures">
        <p><strong>Responsable Titular:</strong> {{ $company->legal_representative_name }}</p>
        <p><strong>RUT Representante:</strong> {{ $company->legal_representative_tax_id }}</p>
        <p><strong>Contacto Oficial ARCO-P:</strong> {{ $company->arco_contact_email }}</p>
        <br>
        <p><strong>Versión del Documento:</strong> {{ $policy->company_version }}</p>
        <p><strong>Fecha de Última Actualización:</strong> {{ $policy->published_at ? $policy->published_at->format('d/m/Y') : 'Borrador' }}</p>
    </div>
</div>
BLADE_COOKIE;

        LegalTemplate::updateOrCreate(
            ['document_type' => 'cookie_policy', 'version' => 1],
            [
                'name' => 'Política de Cookies y Tecnologías de Rastreo',
                'content' => trim($cookiePolicyContent),
                'wizard_schema' => [
                    'steps' => ['Cookies analíticas', 'Cookies de marketing', 'Cookies de funcionalidad'],
                ],
                'is_active' => true,
            ]
        );

        // ==========================================
        // 3. POLÍTICA PARA TRABAJADORES (RRHH)
        // ==========================================
        $workersPolicyContent = <<<'BLADE_WORKERS'
<div class="policy-header">
    <h1>POLÍTICA DE PRIVACIDAD Y PROTECCIÓN DE DATOS PARA TRABAJADORES</h1>
    <p><strong>Empleador (Responsable):</strong> {{ $company->business_name }}</p>
    <p><strong>RUT:</strong> {{ $company->tax_id }}</p>
    <p><strong>Domicilio:</strong> {{ $company->legal_address }}</p>
    <p><strong>Delegado de Protección de Datos (DPO):</strong> {{ $company->dpo_contact['name'] ?? 'Atención directa por Gerencia/RRHH' }}</p>
    <p><strong>Versión:</strong> {{ $policy->company_version }} | <strong>Fecha de Emisión:</strong> {{ $policy->published_at ? $policy->published_at->format('d/m/Y') : 'Borrador' }}</p>
</div>

<p>La presente política regula el tratamiento de los datos personales de los trabajadores de <strong>{{ $company->business_name }}</strong>, garantizando el respeto irrestricto a la dignidad humana, la autonomía de la voluntad y los derechos fundamentales, en estricta conformidad con la Ley 21.719 sobre Protección de Datos Personales y el Artículo 5° del Código del Trabajo.</p>

<hr>

<h2>1. Control y Monitoreo Tecnológico en el Ámbito Laboral</h2>
<p>En el ejercicio de sus facultades de administración y dirección, el Empleador podrá utilizar herramientas tecnológicas para el control de asistencia, seguridad y correcto uso de los recursos corporativos, sujetándose a los principios de proporcionalidad y finalidad.</p>

<ul>
@if($wizard_data['step_1_monitoring']['video'] ?? false)
    <li>
        <strong>1.1. Sistemas de Videovigilancia:</strong> El Empleador utiliza sistemas de grabación de imágenes en las instalaciones con el único fin de resguardar la seguridad física de los trabajadores y los activos de la empresa. Las cámaras se encuentran debidamente señalizadas y en ningún caso vulnerarán la privacidad en zonas de descanso, vestuarios o baños. Las imágenes se almacenarán por un plazo máximo de 30 días, tras lo cual serán suprimidas o anonimizadas, salvo requerimiento de tribunales u organismos públicos en el ámbito de sus competencias.
    </li>
@endif

@if($wizard_data['step_1_monitoring']['biometrics'] ?? false)
    <li>
        <strong>1.2. Control de Asistencia Biométrico:</strong> Para el registro y cumplimiento de la jornada laboral, el Empleador utiliza el sistema <em>{{ htmlspecialchars($wizard_data['step_1_monitoring']['biometrics_system'] ?? 'de control biométrico validado por la Dirección del Trabajo') }}</em>.<br>
        <em>Base Lícita y Garantías:</em> El tratamiento de estos datos sensibles requiere su consentimiento expreso. El Empleador garantiza que no se almacenan las huellas o rasgos físicos en formato crudo, sino mediante plantillas matemáticas cifradas irreversibles.
        <div class="legal-warning" style="margin-top: 10px; font-size: 0.9em; background: #f9f9f9; border-left: 3px solid #ffcc00; padding: 10px;">
            <strong>Nota de Cumplimiento (Art. 16 ter):</strong> El trabajador tiene el derecho a negarse al uso de sistemas biométricos. En tal caso, el Empleador dispondrá de un método alternativo de control de asistencia no invasivo que no implique discriminación laboral.
        </div>
    </li>
@endif

@if($wizard_data['step_1_monitoring']['gps'] ?? false)
    <li>
        <strong>1.3. Sistemas de Geolocalización (GPS):</strong> Los vehículos corporativos o dispositivos móviles asignados cuentan con tecnología de geolocalización (Art. 16 sexies). Su finalidad exclusiva es la gestión logística, optimización de rutas y seguridad en el transporte.
        <br><em>Proporcionalidad:</em> El monitoreo se limita estrictamente al horario de la jornada laboral. Queda estrictamente prohibido el monitoreo en tiempos de descanso o colación para no afectar el derecho a la desconexión del trabajador.
    </li>
@endif

@if($wizard_data['step_1_monitoring']['digital'] ?? false)
    <li>
        <strong>1.4. Monitoreo de Herramientas Digitales:</strong> El correo electrónico corporativo, el acceso a internet, los equipos informáticos y el software provisto son herramientas de propiedad exclusiva del Empleador, entregadas para el desarrollo de las funciones laborales. <strong>{{ $company->business_name }}</strong> se reserva la facultad de auditar estos medios, siempre respetando la privacidad de las comunicaciones estrictamente personales y de acuerdo con los protocolos y límites establecidos en el Reglamento Interno de Orden, Higiene y Seguridad (RIOHS).
    </li>
@endif
</ul>
<hr>

<h2>2. Tratamiento de Datos de Salud y Beneficios Laborales</h2>
<p>En el marco de la relación laboral y la seguridad social, el Empleador podrá tratar categorías especiales de datos con estricto apego a la normativa legal vigente.</p>

@if($wizard_data['step_2_health_benefits']['health_active'] ?? false)
    <h3>2.1. Salud Ocupacional y Obligaciones Legales</h3>
    <p>El Empleador realizará el tratamiento de datos relativos a la salud del trabajador (tales como licencias médicas, resultados de exámenes preocupacionales, evaluaciones de riesgos psicosociales o registros de accidentes laborales) con la finalidad exclusiva de dar cumplimiento a sus obligaciones legales ante la Dirección del Trabajo, las Secretarías Regionales Ministeriales de Salud (SEREMI) y las Mutualidades de Empleadores.</p>
    <ul>
        <li><strong>Base de Licitud:</strong> Este tratamiento es lícito por mandato legal y por ser indispensable para el ejercicio de derechos y el cumplimiento de obligaciones en el ámbito del derecho laboral y de seguridad social (Art. 16, letra e de la Ley 21.719).</li>
        <li><strong>Medidas de Seguridad Reforzadas:</strong> Dada la naturaleza de datos sensibles de esta información, el Empleador garantiza que el acceso a estos registros está restringido exclusivamente al personal de Recursos Humanos, Bienestar y Salud Ocupacional, quienes se encuentran bajo un estricto deber de secreto y confidencialidad que subsiste de manera indefinida, incluso tras el término de la relación laboral.</li>
    </ul>

    <div class="legal-warning" style="margin-top: 10px; font-size: 0.9em; background: #fff3cd; border-left: 3px solid #ffc107; padding: 10px;">
        <strong>Nota de Cumplimiento (Brechas y PIA):</strong> Conforme al Art. 14 sexies de la Ley 21.719, cualquier vulneración de seguridad que afecte estos datos de salud será notificada de forma obligatoria a la Agencia de Protección de Datos Personales y al titular. Asimismo, si este tratamiento se realiza a gran escala, el Responsable declara someter estos procesos a una Evaluación de Impacto (PIA) obligatoria.
    </div>
@endif

@if($wizard_data['step_2_health_benefits']['benefits_active'] ?? false)
    <h3>2.2. Gestión de Beneficios, Seguros y Cargas Familiares</h3>
    <p>Con el fin de gestionar seguros complementarios de salud, convenios de bienestar, cajas de compensación y otros beneficios de carácter social, el Empleador tratará datos personales del trabajador y, cuando corresponda, de sus cargas familiares debidamente acreditadas.</p>
    <ul>
        <li><strong>Finalidad y Licitud:</strong> La información será utilizada únicamente para la inscripción, administración y otorgamiento de los beneficios voluntarios u obligatorios pactados. El tratamiento se basa en la ejecución del contrato de trabajo y sus anexos de beneficios.</li>
        <li><strong>Responsabilidad sobre Datos de Terceros y Menores (NNA):</strong> El trabajador, al suministrar libremente los datos de sus cargas familiares (incluyendo Niños, Niñas y Adolescentes para efectos de asignaciones o seguros), declara bajo su responsabilidad contar con la facultad legal para autorizar dicho tratamiento en su calidad de padre, madre o representante legal, autorizando expresamente al Empleador a procesarlos en estricto resguardo del interés superior de los menores (Art. 16 quáter).</li>
    </ul>
@endif
<hr>

<h2>3. Comunicación y Cesión de Datos a Terceros</h2>
<p><strong>{{ $company->business_name }}</strong> informa que, para el correcto cumplimiento de las obligaciones derivadas del contrato de trabajo y la normativa vigente, sus datos personales pueden ser comunicados o cedidos a los siguientes terceros destinatarios, quienes actúan bajo bases de licitud específicas:</p>

@if($wizard_data['step_3_sharing']['none'] ?? false)
    {{-- CASO: TRATAMIENTO 100% INTERNO --}}
    <p><strong>Tratamiento Interno:</strong> El Responsable informa que el tratamiento de datos de Recursos Humanos se realiza íntegramente de forma interna, no existiendo comunicaciones de datos a proveedores externos de gestión de personal, salvo las cesiones obligatorias por mandato legal a los organismos de seguridad social, salud y tributarios que se detallan a continuación.</p>

    @if($wizard_data['step_3_sharing']['social_security'] ?? true)
        <ul>
            <li><strong>Instituciones de Seguridad Social (Cesión Legal):</strong> En cumplimiento de una obligación legal ineludible (Art. 13, letra b de la Ley 21.719), sus datos personales y previsionales serán cedidos a entidades como Previred, AFP, Isapres, Fonasa, Administradora de Fondos de Cesantía (AFC) y Mutualidades de Empleadores para la declaración, pago de cotizaciones y beneficios sociales.</li>
        </ul>
    @endif

@else
    {{-- CASO: USO DE PROVEEDORES / TERCEROS --}}
    <ul>
        @if($wizard_data['step_3_sharing']['social_security'] ?? true)
            <li><strong>Instituciones de Seguridad Social (Cesión Legal):</strong> En cumplimiento de una obligación legal (Art. 13, letra b de la Ley 21.719), sus datos personales y previsionales serán cedidos a entidades como Previred, AFP, Isapres, Fonasa, AFC y Mutualidades de Empleadores para la declaración y pago de cotizaciones.</li>
        @endif

        @if($wizard_data['step_3_sharing']['hr_software'] ?? false)
            <li><strong>Proveedores de Gestión de RR.HH. (Comunicación por Mandato):</strong> Sus datos de identificación, asistencia y remuneraciones serán comunicados a plataformas o proveedores tecnológicos tales como <em>{{ htmlspecialchars($wizard_data['step_3_sharing']['hr_software_names'] ?? 'plataformas de remuneraciones') }}</em>. Estas entidades actúan en calidad de mandatarios del tratamiento (Art. 15 bis), procesando la información bajo instrucciones estrictas del Empleador, exclusivamente para fines de cálculo de remuneraciones y gestión administrativa.</li>
        @endif

        @if($wizard_data['step_3_sharing']['external_advisors'] ?? false)
            <li><strong>Servicios Profesionales Externos:</strong> Los datos estrictamente necesarios para auditoría contable, defensa jurídica o asesoría en gestión de personas serán comunicados a <em>{{ htmlspecialchars($wizard_data['step_3_sharing']['external_advisors_names'] ?? 'asesores externos') }}</em>. Dichos profesionales se encuentran sujetos al deber de secreto y confidencialidad permanente establecido en el Art. 14 bis de la Ley 21.719.</li>
        @endif

        @if($wizard_data['step_3_sharing']['others'] ?? false)
            <li><strong>Otros Destinatarios Autorizados:</strong> Adicionalmente, se informa la comunicación de datos a <em>{{ htmlspecialchars($wizard_data['step_3_sharing']['others_names'] ?? 'entidades autorizadas') }}</em> con la finalidad específica de: <em>{{ htmlspecialchars($wizard_data['step_3_sharing']['others_purpose'] ?? 'gestiones relacionadas al contrato de trabajo') }}</em>.</li>
        @endif
    </ul>

    <div class="legal-warning" style="margin-top: 10px; font-size: 0.9em; background: #e8f4f8; border-left: 3px solid #17a2b8; padding: 10px;">
        <strong>Nota de Cumplimiento Institucional (Art. 15 bis y Transferencias Internacionales):</strong> El Empleador declara que toda comunicación de datos a proveedores tecnológicos o externos se encuentra regulada por un Contrato de Mandato para el Tratamiento de Datos. Asimismo, si alguno de los proveedores de software (SaaS) mencionados almacena información fuera del territorio nacional de Chile, dicha transferencia internacional se realiza bajo cláusulas contractuales tipo u otros mecanismos lícitos establecidos en los Artículos 27 y 28 de la Ley 21.719, asumiendo el Empleador la responsabilidad solidaria frente al titular por eventuales infracciones del mandatario.
    </div>
@endif
BLADE_WORKERS;

        LegalTemplate::updateOrCreate(
            ['document_type' => 'workers_policy', 'version' => 1],
            [
                'name' => 'Política de Privacidad y Protección de Datos para Trabajadores',
                'content' => trim($workersPolicyContent),
                'wizard_schema' => [
                    'steps' => ['Monitoreo laboral', 'Datos de salud y beneficios', 'Cesión de datos a terceros'],
                ],
                'is_active' => true,
            ]
        );

        // ==========================================
        // 4. PLANTILLA DE DOCUMENTO PERSONALIZADO
        // ==========================================
        $customPolicyContent = <<<'BLADE_CUSTOM'
<div class="policy-header">
    <h1>{{ mb_strtoupper($wizard_data['custom_policy']['title'] ?? 'DOCUMENTO LEGAL PERSONALIZADO') }}</h1>
    <p><strong>Entidad Emisora:</strong> {{ $company->business_name }}</p>
    <p><strong>RUT:</strong> {{ $company->tax_id }}</p>
    <p><strong>Domicilio:</strong> {{ $company->legal_address }}</p>
    <p><strong>Canal de Contacto:</strong> {{ $company->arco_contact_email }}</p>
    <p><strong>Versión:</strong> {{ $policy->company_version }} | <strong>Fecha:</strong> {{ $policy->published_at ? $policy->published_at->format('d/m/Y') : 'Borrador' }}</p>
</div>
<hr>
@if(!($wizard_data['custom_policy']['is_privacy_related'] ?? true))

    {{-- MODO 1: LIENZO LIBRE (Para Términos y Condiciones, Devoluciones, etc.) --}}
    <div class="custom-legal-content">
        {!! $wizard_data['custom_policy']['free_text_html'] ?? '<p>Contenido no definido.</p>' !!}
    </div>
@else
    {{-- MODO 2: ESTRUCTURA OBLIGATORIA LEY 21.719 (Avisos de Privacidad Específicos) --}}
    <p>El presente documento regula el tratamiento de datos personales específico para <strong>{{ $wizard_data['custom_policy']['context'] ?? 'este proceso' }}</strong>, en estricto cumplimiento de la Ley 21.719 de la República de Chile.</p>
    <h2>1. Definición y Finalidades del Tratamiento</h2>
    <p><strong>{{ $company->business_name }}</strong> recolecta y trata las siguientes categorías de datos: <em>{{ $wizard_data['custom_policy']['data_categories'] ?? 'datos de identificación' }}</em>.</p>
    <p>La finalidad exclusiva de este tratamiento es: <strong>{{ $wizard_data['custom_policy']['purposes'] ?? 'gestionar la relación con el titular' }}</strong>. No trataremos sus datos para fines distintos a los aquí informados sin su consentimiento previo o una base legal que lo justifique.</p>
    <h2>2. Base de Licitud</h2>
    <p>Este tratamiento se realiza bajo la siguiente base de licitud (Art. 13 de la Ley 21.719): <strong>{{ $wizard_data['custom_policy']['legal_basis'] ?? 'Consentimiento del titular' }}</strong>.</p>
    <h2>3. Destinatarios y Comunicaciones</h2>
    <p>Sus datos personales podrán ser comunicados a: <em>{{ $wizard_data['custom_policy']['recipients'] ?? 'ningún tercero, salvo obligación legal' }}</em>.</p>
    @if(!empty($wizard_data['custom_policy']['international_transfers']))
        <p><strong>Transferencia Internacional:</strong> Sus datos serán transferidos a <em>{{ $wizard_data['custom_policy']['international_transfers'] }}</em>, operando bajo las salvaguardas legales que establece el Art. 27 de la Ley.</p>
    @else
        <p>El Responsable declara que para este proceso específico no se realizan transferencias de datos fuera del territorio nacional.</p>
    @endif
    <h2>4. Plazos de Conservación</h2>
    <p>Sus datos se conservarán durante el siguiente periodo: <strong>{{ $wizard_data['custom_policy']['retention_period'] ?? 'el tiempo estrictamente necesario para cumplir la finalidad' }}</strong>. Cumplido este plazo, los datos serán eliminados o anonimizados (perdiendo su calidad de dato personal).</p>
    <h2>5. Ejercicio de Derechos ARCO+P</h2>
    <p>Usted tiene derecho de Acceso, Rectificación, Cancelación (Supresión), Oposición y Portabilidad sobre su información. Puede ejercer estos derechos en cualquier momento enviando una solicitud formal a <strong>{{ $company->arco_contact_email }}</strong>. En caso de no recibir respuesta dentro de los plazos legales, le asiste el derecho a recurrir ante la Agencia de Protección de Datos Personales.</p>
@endif
<hr>
<div class="policy-footer">
    <div class="legal-signatures">
        <p><strong>Representante Legal:</strong> {{ $company->legal_representative_name }}</p>
        <p><strong>RUT Representante:</strong> {{ $company->legal_representative_tax_id }}</p>
        <p>Documento generado mediante plataforma tecnológica de cumplimiento.</p>
    </div>
</div>

BLADE_CUSTOM;

        LegalTemplate::updateOrCreate(
            ['document_type' => 'custom_policy', 'version' => 1],
            [
                'name' => 'Documento Legal Personalizado / Aviso Específico',
                'content' => trim($customPolicyContent),
                'wizard_schema' => [
                    'steps' => ['Clasificación legal', 'Redacción libre', 'Contexto de tratamiento', 'Categorías de datos', 'Finalidades', 'Base de licitud', 'Destinatarios', 'Transferencias internacionales', 'Plazos de retención'],
                ],
                'is_active' => true,
            ]
        );
        $this->command->info('Legal Templates seeded successfully!');
    }
}

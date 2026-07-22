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
@endphp

<h2>2. Tratamiento de Categorías Especiales de Datos (Datos Sensibles)</h2>
<p>El Responsable informa que, para la prestación de sus servicios, realiza el tratamiento de las siguientes categorías de datos sensibles: <strong>{{ implode(', ', $selected_sensitive) }}</strong>.</p>

<strong>2.1. Base de Licitud para Datos Sensibles:</strong>
<p>Para el tratamiento de la información sensible recopilada, nuestra base de licitud es su <strong>Consentimiento Expreso, Específico e Informado</strong> (Artículo 16 de la Ley 21.719). Este no se presume ni se obtiene de forma tácita; se recaba mediante una declaración escrita o un medio tecnológico que permite acreditar fehacientemente su voluntad.</p>

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
    <p>Los servicios y el sitio web de {{ $company->business_name }} están dirigidos exclusivamente a personas mayores de 18 años. El Responsable no recopila ni solicita intencionadamente datos personales de menores de edad. En caso de que se tome conocimiento de la existencia de datos de un menor en nuestras bases de datos sin la debida autorización de su representante legal, se procederá a su eliminación inmediata en cumplimiento de la obligación especial de protección establecida en el Art. 16 quáter de la Ley 21.719 y conforme al Principio de Proporcionalidad (Art. 3, letra c).</p>
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

        $age_groups = $wizard_data['step_3_minors']['age_groups'] ?? ['under_14', '14_to_18'];
        $has_under_14 = in_array('under_14', $age_groups);
        $has_14_to_18 = in_array('14_to_18', $age_groups);
        $adolescents_sensitive = $wizard_data['step_3_minors']['adolescents_sensitive'] ?? false;

        // Lógica cruzada: Datos Sensibles + Menores
        $has_sensitive = !in_array('ninguna', $wizard_data['step_2_sensitive_data'] ?? ['ninguna']);
    @endphp

    <h2>3. Tratamiento de Datos de Niños, Niñas y Adolescentes (NNA)</h2>
    <p>En cumplimiento del Art. 16 quáter de la Ley 21.719, {{ $company->business_name }} realiza el tratamiento de datos de menores de edad bajo el estricto respeto a su interés superior y su autonomía progresiva.</p>

    <p><strong>3.1. Finalidad del Tratamiento:</strong> Los datos de NNA son tratados exclusivamente para las siguientes finalidades: <strong>{{ implode(', ', $selected_minors_purposes) }}</strong>.</p>

    <p><strong>3.2. Consentimiento y Validación:</strong></p>
    <ul>
        @if($has_under_14)
        <li><strong>Niños y Niñas (menores de 14 años):</strong> El tratamiento solo se realiza contando con la autorización previa de sus padres o representantes legales, verificada mediante: <em>{{ $wizard_data['step_3_minors']['verification_method'] ?? 'mecanismo de verificación formal' }}</em>.</li>
        @endif
        @if($has_14_to_18)
        <li><strong>Adolescentes (14 a 18 años):</strong> Podrán autorizar por sí mismos el tratamiento de sus datos comunes, ejerciendo su autonomía progresiva conforme al Art. 16 quáter.</li>
            @if($adolescents_sensitive)
            <li><strong>Adolescentes y Datos Sensibles (menores de 16 años):</strong> Cuando el tratamiento involucre datos sensibles de adolescentes menores de 16 años, se requerirá obligatoriamente el consentimiento expreso del padre, madre o representante legal, conforme al inciso cuarto del Art. 16 quáter de la Ley.</li>
            @endif
        @endif
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
        'squarespace' => 'Squarespace, Inc. (EE.UU.) - Plataforma',
        'mailchimp' => 'The Rocket Science Group LLC (EE.UU.) - Mailchimp',
        'hubspot' => 'HubSpot, Inc. (EE.UU.) - CRM',
        'salesforce' => 'Salesforce, Inc. (EE.UU.) - CRM',
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

    <p><strong>5.1. Lógica del Tratamiento:</strong> <em>{{ $wizard_data['step_5_ai']['logic'] ?? 'análisis y evaluación de perfil' }}</em>.</p>

    <p><strong>5.2. Derechos ARCO-P y Garantías Algorítmicas:</strong> De conformidad con el Art. 8 bis de la Ley, usted tiene derecho a no ser objeto de decisiones basadas únicamente en el tratamiento automatizado de sus datos que produzcan efectos jurídicos sobre usted. En consecuencia, podrá ejercer en cualquier momento ante <strong>{{ $company->arco_contact_email }}</strong> los siguientes derechos:</p>
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

<h2>7. Ejercicio de los Derechos ARCO+P</h2>
<p>En conformidad con los Artículos 8 y 8 bis de la Ley 21.719, usted tiene el derecho inalienable de ejercer en cualquier momento los siguientes derechos frente al Responsable del Tratamiento:</p>
<ul>
    <li><strong>Derecho de Acceso:</strong> Solicitar y obtener información clara y completa sobre la existencia de sus datos personales en nuestros registros, su origen, las finalidades del tratamiento, las categorías de datos tratados y los destinatarios de las comunicaciones realizadas.</li>
    <li><strong>Derecho de Rectificación:</strong> Solicitar la corrección o actualización de sus datos personales que resulten inexactos, incompletos o desactualizados, para garantizar su conformidad con la realidad.</li>
    <li><strong>Derecho de Cancelación (Supresión):</strong> Solicitar la eliminación de sus datos personales de nuestras bases de datos cuando el tratamiento no se ajuste a la ley, haya cesado la finalidad que lo motivó, o cuando usted retire su consentimiento siendo este la única base de licitud aplicable.</li>
    <li><strong>Derecho de Oposición:</strong> Oponerse al tratamiento de sus datos personales cuando existan motivos legítimos relativos a su situación particular, o cuando el tratamiento tenga como finalidad la mercadotecnia directa o la elaboración de perfiles.</li>
    <li><strong>Derecho de Portabilidad:</strong> Solicitar la transferencia de sus datos personales a otro Responsable del Tratamiento, en formato estructurado, de uso común y lectura mecánica, cuando el tratamiento se base en el consentimiento o en la ejecución de un contrato y se realice por medios automatizados.</li>
</ul>
<p><strong>Forma de Ejercicio:</strong> Para ejercer cualquiera de estos derechos, podrá dirigir su solicitud formal y fundamentada al correo electrónico <strong>{{ $company->arco_contact_email }}</strong>, identificándose debidamente e indicando el derecho que desea ejercer. El Responsable atenderá su solicitud dentro del plazo máximo de 10 días hábiles contados desde la recepción de la misma, conforme al Art. 12 de la Ley.</p>
<p>En caso de no recibir respuesta dentro del plazo legal, o si la respuesta fuese desfavorable, usted tiene el derecho a recurrir ante la Agencia de Protección de Datos Personales para que ésta conozca y resuelva su reclamación.</p>

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
                    'steps' => [
                        [
                            'title' => 'Funciones de su sitio web',
                            'fields' => [
                                ['key' => 'step_1_website_functions', 'label' => '¿Para qué usa su sitio web o aplicación?', 'type' => 'multiselect', 'options' => ['informativa' => ['label' => 'Informativa / Contacto — Solo tiene formularios de contacto y cotizaciones', 'legal_purposes' => ['contractual_execution']], 'ecommerce' => ['label' => 'E-commerce / Ventas — Vende productos o servicios y procesa pagos online', 'legal_purposes' => ['contractual_execution', 'legal_compliance']], 'saas' => ['label' => 'SaaS / Plataforma — Los usuarios crean cuentas y suben información', 'legal_purposes' => ['contractual_execution', 'service_improvement']]], 'help_text' => 'Seleccione todas las que apliquen. Cada opción agrega las cláusulas legales correspondientes. Base legal: Informativa = medidas precontractuales (Art. 13 letra c); E-commerce = obligaciones tributarias (Art. 13 letra b) y contrato de compraventa (Art. 13 letra c); SaaS = contrato de servicios digitales (Art. 13 letra c).'],
                            ],
                        ],
                        [
                            'title' => 'Datos sensibles',
                            'fields' => [
                                ['key' => 'step_2_sensitive_data', 'label' => '¿Su negocio solicita o almacena algún tipo de dato sensible?', 'type' => 'multiselect', 'options' => ['salud' => ['label' => 'Datos de Salud — Fichas clínicas, recetas, diagnósticos', 'legal_purposes' => ['health_occupational']], 'biometria' => ['label' => 'Datos Biométricos — Huella digital, reconocimiento facial', 'legal_purposes' => ['biometric_identification']], 'politica' => ['label' => 'Afiliación Política', 'legal_purposes' => []], 'sindical' => ['label' => 'Afiliación Sindical o Gremial', 'legal_purposes' => []], 'religion' => ['label' => 'Creencias Religiosas', 'legal_purposes' => []], 'sexual' => ['label' => 'Orientación Sexual', 'legal_purposes' => []], 'racial' => ['label' => 'Origen Racial o Étnico', 'legal_purposes' => []], 'otros' => ['label' => 'Otra categoría de dato sensible', 'legal_purposes' => []], 'ninguna' => ['label' => 'Ninguna de las anteriores', 'legal_purposes' => []]], 'help_text' => 'Los datos sensibles requieren protección reforzada. Si marca cualquiera (salvo "Ninguna"), se agrega una cláusula especial con consentimiento explícito. Base legal: Art. 16 exige consentimiento expreso e informado; Art. 16 ter regula datos biométricos.'],
                                ['key' => 'step_2_sensitive_data_other', 'label' => 'Describa la categoría sensible adicional', 'type' => 'text', 'show_if' => ['key' => 'step_2_sensitive_data', 'value' => 'otros'], 'requires_purpose_selection' => true, 'help_text' => 'Ej: Datos genéticos, datos sobre vida sexual. Base legal: Toda categoría sensible queda cubierta por el Art. 16.'],
                            ],
                        ],
                        [
                            'title' => 'Menores de edad',
                            'fields' => [
                                ['key' => 'step_3_minors_active', 'label' => '¿Su organización maneja datos personales de menores de 18 años?', 'type' => 'boolean', 'help_text' => 'Incluye cualquier situación donde pida, guarde o use datos de menores. Base legal: Art. 16 quáter exige protección reforzada, respetando el interés superior y autonomía progresiva del menor.'],
                                ['key' => 'step_3_minors_age_groups', 'label' => '¿Qué rangos de edad de menores trata?', 'type' => 'multiselect', 'options' => ['under_14' => ['label' => 'Menores de 14 años (Niños/as) — Requieren autorización del padre o tutor', 'legal_purposes' => []], '14_to_18' => ['label' => 'De 14 a 18 años (Adolescentes) — Pueden autorizar por sí mismos, salvo datos sensibles', 'legal_purposes' => []]], 'show_if' => ['key' => 'step_3_minors_active', 'value' => true], 'help_text' => 'Seleccione todos los que apliquen. Las reglas de consentimiento cambían según el rango de edad. Base legal: Art. 16 quáter — menores de 14 siempre requieren representante legal; de 14 a 18 ejercen autonomía progresiva, pero datos sensibles de menores de 16 requieren consentimiento del padre/tutor.'],
                                ['key' => 'step_3_minors_purposes', 'label' => '¿Para qué necesita los datos de los menores?', 'type' => 'multiselect', 'options' => ['servicio' => ['label' => 'Servicio principal — Educación, salud o plataforma para menores', 'legal_purposes' => ['contractual_execution']], 'seguridad' => ['label' => 'Seguridad — Control de acceso, videovigilancia, validación de identidad', 'legal_purposes' => ['service_improvement']], 'legal' => ['label' => 'Obligación legal — Requerimientos de autoridades o registros públicos', 'legal_purposes' => ['legal_compliance']], 'marketing' => ['label' => 'Marketing — Descuentos, clubes infantiles, concursos', 'legal_purposes' => ['marketing_direct']], 'otros' => ['label' => 'Otra finalidad', 'legal_purposes' => []]], 'show_if' => ['key' => 'step_3_minors_active', 'value' => true], 'help_text' => 'Seleccione todas las que apliquen. Aplica para todos los rangos de edad seleccionados.'],
                                ['key' => 'step_3_minors_other_purpose', 'label' => 'Describa la finalidad adicional', 'type' => 'text', 'show_if' => ['key' => 'step_3_minors_purposes', 'value' => 'otros'], 'requires_purpose_selection' => true, 'help_text' => 'Ej: Actividades recreativas, programas de mentoría.'],
                                ['key' => 'step_3_minors_verification_method', 'label' => '¿Cómo obtiene la autorización del padre, madre o tutor?', 'type' => 'text', 'show_if' => ['key' => 'step_3_minors_age_groups', 'value' => 'under_14'], 'help_text' => 'Ej: Firma en contrato de matrícula, correo de confirmación del adulto. Base legal: Art. 16 quáter exige verificar la identidad del representante legal para menores de 14.'],
                                ['key' => 'step_3_minors_adolescents_sensitive', 'label' => '¿Trata datos sensibles (salud, biometría, etc.) de adolescentes entre 14 y 16 años?', 'type' => 'boolean', 'show_if' => ['key' => 'step_3_minors_age_groups', 'value' => '14_to_18'], 'help_text' => 'Si trata datos sensibles de menores de 16, necesita consentimiento expreso del padre o tutor, aunque el adolescente tenga más de 14 años. Base legal: Art. 16 quáter inciso cuarto — datos sensibles de menores de 16 siempre requieren autorización del representante.'],
                            ],
                        ],
                        [
                            'title' => 'Proveedores y infraestructura',
                            'fields' => [
                                ['key' => 'step_4_providers', 'label' => '¿Qué servicios externos utiliza para operar su sitio web?', 'type' => 'multiselect', 'options' => ['local' => ['label' => 'Servidor Local en Chile — Los datos se quedan en el país', 'legal_purposes' => []], 'google_analytics' => ['label' => 'Google Analytics / Google Ads (EE.UU.)', 'legal_purposes' => ['analytics_behavior', 'international_transfer']], 'meta' => ['label' => 'Meta Pixel / Facebook Ads (EE.UU.)', 'legal_purposes' => ['marketing_direct', 'marketing_profiling', 'international_transfer']], 'shopify' => ['label' => 'Shopify — Plataforma de e-commerce (Canadá)', 'legal_purposes' => ['contractual_execution', 'international_transfer']], 'wix' => ['label' => 'Wix — Plataforma web (Israel)', 'legal_purposes' => ['contractual_execution', 'international_transfer']], 'squarespace' => ['label' => 'Squarespace — Plataforma web (EE.UU.)', 'legal_purposes' => ['contractual_execution', 'international_transfer']], 'mailchimp' => ['label' => 'Mailchimp — Email marketing (EE.UU.)', 'legal_purposes' => ['marketing_direct', 'international_transfer']], 'hubspot' => ['label' => 'HubSpot — CRM y marketing (EE.UU.)', 'legal_purposes' => ['marketing_direct', 'marketing_profiling', 'international_transfer']], 'salesforce' => ['label' => 'Salesforce — CRM empresarial (EE.UU.)', 'legal_purposes' => ['marketing_direct', 'marketing_profiling', 'international_transfer']], 'aws' => ['label' => 'Amazon Web Services — Cloud (EE.UU.)', 'legal_purposes' => ['international_transfer', 'service_improvement']], 'azure' => ['label' => 'Microsoft Azure — Cloud (EE.UU.)', 'legal_purposes' => ['international_transfer', 'service_improvement']], 'google_cloud' => ['label' => 'Google Cloud — Hosting (EE.UU.)', 'legal_purposes' => ['international_transfer', 'service_improvement']], 'otros' => ['label' => 'Otro proveedor extranjero', 'legal_purposes' => ['international_transfer']]], 'help_text' => 'Marque todos los que apliquen. Si selecciona cualquier proveedor extranjero, se agregará la cláusula de transferencias internacionales. Base legal: Art. 14 ter letra h y Arts. 27-28 regulan transferencias internacionales; proveedores actúan como mandatarios bajo el Art. 15 bis.'],
                                ['key' => 'step_4_other_provider', 'label' => 'Nombre y país del proveedor adicional', 'type' => 'text', 'show_if' => ['key' => 'step_4_providers', 'value' => 'otros'], 'requires_purpose_selection' => true, 'help_text' => 'Ej: Cloudflare (EE.UU.), DataDog (EE.UU.).'],
                            ],
                        ],
                        [
                            'title' => 'Decisiones automatizadas e IA',
                            'fields' => [
                                ['key' => 'step_5_ai_active', 'label' => '¿Usa algoritmos o inteligencia artificial para tomar decisiones sobre los clientes?', 'type' => 'boolean', 'legal_purposes' => ['marketing_profiling'], 'help_text' => 'Ej: aprobación automática de créditos, scoring de riesgo, precios personalizados. Base legal: Art. 14 ter letra l y Art. 8 bis regulan decisiones automatizadas y dan derecho a explicación e intervención humana.'],
                                ['key' => 'step_5_ai_logic', 'label' => '¿Qué hace exactamente el sistema? Descríbalo en palabras simples', 'type' => 'text', 'show_if' => ['key' => 'step_5_ai_active', 'value' => true], 'help_text' => 'Ej: Evalúa ingresos e historial comercial para aprobar o rechazar créditos. Base legal: Art. 14 ter letra l exige informar la lógica del tratamiento automatizado.'],
                            ],
                        ],
                        [
                            'title' => 'Plazos de conservación',
                            'fields' => [
                                ['key' => 'step_6_retention', 'label' => '¿Por cuánto tiempo conservará los datos de sus usuarios?', 'type' => 'multiselect', 'options' => ['tributario' => ['label' => '6 años por ley — Obligación tributaria y comercial (SII)', 'legal_purposes' => ['legal_compliance']], 'cuentas' => ['label' => 'Mientras la cuenta esté activa — Se eliminan al borrar la cuenta', 'legal_purposes' => ['contractual_execution']], 'marketing' => ['label' => 'Hasta que el usuario lo pida — Puede cancelar en cualquier momento', 'legal_purposes' => ['marketing_direct']], 'personalizado' => ['label' => 'Plazo personalizado — Usted define el período', 'legal_purposes' => []]], 'help_text' => 'Seleccione todas las que apliquen. Base legal: Principio de Proporcionalidad (Art. 3 letra c) exige conservar datos solo el tiempo necesario. Obligaciones tributarias exigen mínimo 6 años (SII).'],
                                ['key' => 'step_6_retention_account_days', 'label' => 'Días máximos para eliminar datos tras solicitud de eliminación de cuenta', 'type' => 'text', 'show_if' => ['key' => 'step_6_retention', 'value' => 'cuentas'], 'help_text' => 'Ej: 30 días. Base legal: Debe ser un plazo razonable según Art. 3 letra c.'],
                                ['key' => 'step_6_retention_custom_period', 'label' => 'Describa el plazo personalizado', 'type' => 'text', 'show_if' => ['key' => 'step_6_retention', 'value' => 'personalizado'], 'help_text' => 'Ej: 6 meses desde la última compra, 2 años desde la última interacción. Base legal: Debe ser proporcional a la finalidad (Art. 3 letra c).'],
                            ],
                        ],
                    ],
                ],
                'required_condition' => ['key' => 'has_digital_presence'],
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
         'hotjar_clarity' => 'Hotjar / Microsoft Clarity (Mapas de calor)',
         'mixpanel_amplitude' => 'Mixpanel / Amplitude (Analítica de eventos)'
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
         'tiktok_linkedin' => 'TikTok Inc. / LinkedIn Corporation (TikTok Pixel / LinkedIn Insight Tag)'
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
         'chats' => 'Chats de Atención al Cliente (WhatsApp, Intercom, Zendesk)',
         'multimedia' => 'Reproductores Multimedia Incrustados (YouTube, Vimeo, Spotify)',
         'maps' => 'Mapas Interactivos (Google Maps)'
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
                    'steps' => [
                        [
                            'title' => 'Analítica y Estadísticas',
                            'fields' => [
                                ['key' => 'step_2_analytics_active', 'label' => '¿Su sitio web usa herramientas para contar visitas o entender cómo navegan los usuarios?', 'type' => 'boolean', 'help_text' => 'Incluye Google Analytics, mapas de calor, etc. Se pedirá consentimiento antes de cargarlas. Base legal: Art. 14 ter exige consentimiento previo para cookies no esenciales; Art. 15 bis (mandatarios); Arts. 27-28 (transferencias internacionales).'],
                                ['key' => 'step_2_analytics_providers', 'label' => '¿Cuál de estas herramientas utiliza?', 'type' => 'multiselect', 'options' => ['google_analytics' => ['label' => 'Google Analytics — Estadísticas de visitas (EE.UU.)', 'legal_purposes' => ['analytics_behavior', 'international_transfer']], 'hotjar_clarity' => ['label' => 'Hotjar / Clarity — Mapas de calor y grabaciones', 'legal_purposes' => ['analytics_behavior', 'international_transfer']], 'mixpanel_amplitude' => ['label' => 'Mixpanel / Amplitude — Análisis de eventos', 'legal_purposes' => ['analytics_behavior', 'international_transfer']], 'otros' => ['label' => 'Otra herramienta de analítica', 'legal_purposes' => ['analytics_behavior', 'international_transfer']]], 'show_if' => ['key' => 'step_2_analytics_active', 'value' => true], 'help_text' => 'Seleccione todas las que apliquen.'],
                                ['key' => 'step_2_analytics_other_provider', 'label' => 'Nombre de la herramienta de analítica adicional', 'type' => 'text', 'show_if' => ['key' => 'step_2_analytics_providers', 'value' => 'otros'], 'requires_purpose_selection' => true, 'help_text' => 'Ej: Matomo, Adobe Analytics, Plausible.'],
                            ],
                        ],
                        [
                            'title' => 'Publicidad y Seguimiento',
                            'fields' => [
                                ['key' => 'step_3_marketing_active', 'label' => '¿Muestra anuncios personalizados o rastrea conversiones desde redes sociales?', 'type' => 'boolean', 'help_text' => 'Ej: un cliente ve un producto en su web y luego le aparece un anuncio en Facebook. Siempre requiere consentimiento expreso. Base legal: Art. 8 bis (derecho a oponerse al perfilamiento); Art. 14 ter letra l (decisiones automatizadas); Arts. 27-28 (transferencias internacionales).'],
                                ['key' => 'step_3_marketing_providers', 'label' => '¿Cuál de estas herramientas de publicidad utiliza?', 'type' => 'multiselect', 'options' => ['meta_pixel' => ['label' => 'Meta Pixel — Anuncios en Facebook e Instagram (EE.UU.)', 'legal_purposes' => ['marketing_direct', 'marketing_profiling', 'international_transfer']], 'google_ads' => ['label' => 'Google Ads — Remarketing y conversiones (EE.UU.)', 'legal_purposes' => ['marketing_direct', 'marketing_profiling', 'international_transfer']], 'tiktok_linkedin' => ['label' => 'TikTok Pixel / LinkedIn Insight Tag', 'legal_purposes' => ['marketing_direct', 'marketing_profiling', 'international_transfer']], 'otros' => ['label' => 'Otra herramienta de publicidad', 'legal_purposes' => ['marketing_direct', 'marketing_profiling', 'international_transfer']]], 'show_if' => ['key' => 'step_3_marketing_active', 'value' => true], 'help_text' => 'Seleccione todas las que apliquen.'],
                                ['key' => 'step_3_marketing_other_provider', 'label' => 'Nombre de la herramienta de publicidad adicional', 'type' => 'text', 'show_if' => ['key' => 'step_3_marketing_providers', 'value' => 'otros'], 'requires_purpose_selection' => true, 'help_text' => 'Ej: Pinterest Tag, Snapchat Pixel.'],
                            ],
                        ],
                        [
                            'title' => 'Herramientas Externas (Chat, Video, Mapas)',
                            'fields' => [
                                ['key' => 'step_4_functionality_active', 'label' => '¿Su sitio web tiene elementos de otras empresas incrustados (como chats, videos o mapas)?', 'type' => 'boolean', 'help_text' => 'Ej: un botón de WhatsApp, un video de YouTube, o un mapa de Google. Por ley, deben bloquearse hasta que el usuario acepte. Base legal: Art. 14 quáter (privacidad por diseño, no cargar sin consentimiento); Art. 14 ter letra h (informar transferencias internacionales).'],
                                ['key' => 'step_4_functionality_providers', 'label' => '¿Qué tipo de elementos tiene integrados?', 'type' => 'multiselect', 'options' => ['chats' => ['label' => 'Chats — WhatsApp, Intercom, Zendesk', 'legal_purposes' => ['functional_third_party', 'international_transfer']], 'multimedia' => ['label' => 'Videos o audio — YouTube, Vimeo, Spotify', 'legal_purposes' => ['functional_third_party', 'international_transfer']], 'maps' => ['label' => 'Mapas — Google Maps en sección Contacto', 'legal_purposes' => ['functional_third_party', 'international_transfer']], 'otros' => ['label' => 'Otra herramienta externa', 'legal_purposes' => ['functional_third_party', 'international_transfer']]], 'show_if' => ['key' => 'step_4_functionality_active', 'value' => true], 'help_text' => 'Seleccione todas las que apliquen.'],
                                ['key' => 'step_4_functionality_other_provider', 'label' => 'Nombre de la herramienta externa adicional', 'type' => 'text', 'show_if' => ['key' => 'step_4_functionality_providers', 'value' => 'otros'], 'requires_purpose_selection' => true, 'help_text' => 'Ej: Calendly, Typeform, TripAdvisor widget.'],
                            ],
                        ],
                    ],
                ],
                'required_condition' => ['key' => 'has_digital_presence'],
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
        <strong>1.1. Sistemas de Videovigilancia:</strong> El tratamiento de imágenes mediante sistemas de videovigilancia se realiza en ejercicio de las facultades de administración y dirección del empleador (Art. 5 del Código del Trabajo) y bajo los principios de proporcionalidad y finalidad (Art. 3 de la Ley 21.719), con el objeto de garantizar la seguridad de las personas, bienes e instalaciones de la empresa. Las cámaras se encuentran debidamente señalizadas y en ningún caso vulnerarán la privacidad en zonas de descanso, vestuarios o baños. Las imágenes se almacenarán por un plazo máximo de 30 días, tras lo cual serán suprimidas o anonimizadas, salvo requerimiento de tribunales u organismos públicos en el ámbito de sus competencias.
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
        @if(!empty($wizard_data['step_1_monitoring']['gps_retention']))
            <br><em>Conservación:</em> Los datos de geolocalización se conservarán por un plazo de <strong>{{ htmlspecialchars($wizard_data['step_1_monitoring']['gps_retention']) }}</strong>, tras el cual serán eliminados o anonimizados.
        @else
            <br><em>Conservación:</em> Los datos de geolocalización se conservarán únicamente por el tiempo necesario para la finalidad indicada, tras lo cual serán eliminados o anonimizados.
        @endif
        @if(!empty($wizard_data['step_1_monitoring']['gps_sharing']))
            <br><em>Comunicación a terceros:</em> {{ htmlspecialchars($wizard_data['step_1_monitoring']['gps_sharing']) }}.
        @else
            <br><em>Comunicación a terceros:</em> Los datos de geolocalización no serán comunicados a terceros, salvo obligación legal.
        @endif
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
            <li><strong>Servicios Profesionales Externos:</strong> Los datos estrictamente necesarios para auditoría contable, defensa jurídica o asesoría en gestión de personas serán comunicados a contadores o estudios jurídicos externos. Dichos profesionales se encuentran sujetos al deber de secreto y confidencialidad permanente establecido en el Art. 14 bis de la Ley 21.719.</li>
        @endif

        @if($wizard_data['step_3_sharing']['others'] ?? false)
            <li><strong>Otros Destinatarios Autorizados:</strong> Adicionalmente, se informa la comunicación de datos a <em>{{ htmlspecialchars($wizard_data['step_3_sharing']['others_names'] ?? 'entidades autorizadas') }}</em> con la finalidad específica de: <em>gestiones relacionadas al contrato de trabajo</em>.</li>
        @endif
    </ul>

    <div class="legal-warning" style="margin-top: 10px; font-size: 0.9em; background: #e8f4f8; border-left: 3px solid #17a2b8; padding: 10px;">
        <strong>Nota de Cumplimiento Institucional (Art. 15 bis y Transferencias Internacionales):</strong> El Empleador declara que toda comunicación de datos a proveedores tecnológicos o externos se encuentra regulada por un Contrato de Mandato para el Tratamiento de Datos. Asimismo, si alguno de los proveedores de software (SaaS) mencionados almacena información fuera del territorio nacional de Chile, dicha transferencia internacional se realiza bajo cláusulas contractuales tipo u otros mecanismos lícitos establecidos en los Artículos 27 y 28 de la Ley 21.719, asumiendo el Empleador la responsabilidad solidaria frente al titular por eventuales infracciones del mandatario.
    </div>
@endif

<hr>

<h2>4. Ejercicio de los Derechos ARCO+P del Trabajador</h2>
<p>En conformidad con los Artículos 8 y 8 bis de la Ley 21.719 y sin perjuicio de los derechos laborales consagrados en el Código del Trabajo, el trabajador tiene el derecho inalienable de ejercer en cualquier momento los siguientes derechos frente al Empleador en su calidad de Responsable del Tratamiento:</p>
<ul>
    <li><strong>Derecho de Acceso:</strong> Solicitar y obtener información completa sobre los datos personales que el Empleador mantiene en sus registros, su origen, finalidades del tratamiento y los posibles destinatarios de comunicaciones.</li>
    <li><strong>Derecho de Rectificación:</strong> Solicitar la corrección de datos personales inexactos, incompletos o desactualizados, especialmente aquellos relativos a información previsional, remuneracional y de contacto.</li>
    <li><strong>Derecho de Cancelación (Supresión):</strong> Solicitar la eliminación de datos personales cuyo tratamiento no se ajuste a la ley o haya cesado la finalidad que lo motivó, sin perjuicio de las obligaciones legales de conservación de registros laborales y previsionales.</li>
    <li><strong>Derecho de Oposición:</strong> Oponerse al tratamiento de sus datos para finalidades distintas a las estrictamente laborales o legales, particularmente en lo referente a monitoreo tecnológico y comunicaciones no relacionadas con la relación laboral.</li>
    <li><strong>Derecho de Portabilidad:</strong> Solicitar la transferencia de sus datos personales a otro Responsable en formato estructurado y de uso común, cuando el tratamiento se realice por medios automatizados.</li>
</ul>
<p><strong>Forma de Ejercicio:</strong> El trabajador podrá dirigir su solicitud formal al correo electrónico <strong>{{ $company->arco_contact_email }}</strong> o directamente ante el departamento de Recursos Humanos, identificándose debidamente e indicando el derecho que desea ejercer. El Empleador responderá dentro del plazo máximo de 10 días hábiles, conforme al Art. 12 de la Ley. En caso de respuesta desfavorable o silencio, el trabajador podrá recurrir ante la Agencia de Protección de Datos Personales.</p>

<p><strong>Garantía de No Discriminación y Ausencia de Represalias:</strong> De conformidad con el Art. 4 de la Ley 21.719, los derechos de acceso, rectificación, supresión, oposición y portabilidad son irrenunciables y no pueden ser limitados por ningún acto del empleador. En consecuencia, {{ $company->business_name }} garantiza que el ejercicio legítimo de estos derechos por parte del trabajador no dará lugar a represalias, medidas disciplinarias ni perjuicios en su carrera profesional. Se informa expresamente que cualquier acto destinado a impedir u obstaculizar el ejercicio de estos derechos constituye una infracción grave a la normativa de protección de datos (Art. 34 ter, letra e), sin perjuicio de las acciones de tutela laboral por vulneración de derechos fundamentales que correspondan ante los tribunales competentes.</p>

BLADE_WORKERS;

        LegalTemplate::updateOrCreate(
            ['document_type' => 'workers_policy', 'version' => 1],
            [
                'name' => 'Política de Privacidad y Protección de Datos para Trabajadores',
                'content' => trim($workersPolicyContent),
                'wizard_schema' => [
                    'steps' => [
                        [
                            'title' => 'Control y monitoreo laboral',
                            'fields' => [
                                ['key' => 'step_1_monitoring', 'label' => '¿Usa alguno de estos sistemas para controlar o monitorear a sus trabajadores?', 'type' => 'multiselect', 'options' => ['video' => ['label' => 'Cámaras de seguridad — Videovigilancia en las instalaciones', 'legal_purposes' => ['service_improvement']], 'biometria' => ['label' => 'Control biométrico de asistencia — Huella digital, reconocimiento facial', 'legal_purposes' => ['biometric_identification']], 'gps' => ['label' => 'GPS en vehículos o dispositivos — Rastreo de ubicación', 'legal_purposes' => ['geolocation_tracking']], 'digital' => ['label' => 'Monitoreo digital — Correo electrónico, internet o software de la empresa', 'legal_purposes' => ['service_improvement']]], 'help_text' => 'Seleccione todas las que apliquen. Base legal: Art. 5 del Código del Trabajo faculta al empleador para ejercer control, sujeto a proporcionalidad (Art. 3 Ley 21.719). Art. 16 sexies regula la geolocalización.'],
                                ['key' => 'step_1_monitoring_biometrics_system', 'label' => '¿Qué sistema biométrico utiliza?', 'type' => 'text', 'show_if' => ['key' => 'step_1_monitoring', 'value' => 'biometria'], 'help_text' => 'Ej: Reloj control con huella digital, reconocimiento facial en torniquete. Base legal: Art. 16 ter exige informar el sistema biométrico y su finalidad exclusiva de identificación.'],
                                ['key' => 'step_1_monitoring_gps_retention', 'label' => '¿Por cuánto tiempo guarda los datos de GPS?', 'type' => 'text', 'show_if' => ['key' => 'step_1_monitoring', 'value' => 'gps'], 'help_text' => 'Ej: 30 días, 6 meses, mientras dure la relación laboral. Base legal: Art. 16 sexies exige definir el plazo de conservación.'],
                                ['key' => 'step_1_monitoring_gps_sharing', 'label' => '¿Comparte los datos de GPS con alguien más?', 'type' => 'text', 'show_if' => ['key' => 'step_1_monitoring', 'value' => 'gps'], 'help_text' => 'Ej: No se comparten / Se comparten con la empresa de logística X. Base legal: Art. 16 sexies exige informar si los datos de geolocalización se comunican a terceros.'],
                            ],
                        ],
                        [
                            'title' => 'Datos de salud y beneficios',
                            'fields' => [
                                ['key' => 'step_2_health_benefits', 'label' => '¿Qué tipo de información médica o de beneficios maneja de sus trabajadores?', 'type' => 'multiselect', 'options' => ['salud' => ['label' => 'Salud ocupacional — Licencias médicas, exámenes preocupacionales, accidentes laborales', 'legal_purposes' => ['health_occupational']], 'beneficios' => ['label' => 'Beneficios y cargas familiares — Seguros complementarios, cajas de compensación, datos de familiares', 'legal_purposes' => ['contractual_execution']]], 'help_text' => 'Seleccione todas las que apliquen. Base legal: Art. 16 letra e permite el tratamiento de datos de salud por obligación legal (seguridad social, medicina ocupacional). Los datos de beneficios se basan en la ejecución del contrato de trabajo.'],
                            ],
                        ],
                        [
                            'title' => 'Compartir datos con terceros',
                            'fields' => [
                                ['key' => 'step_3_sharing', 'label' => '¿A qué tipo de proveedores envía los datos de sus trabajadores?', 'type' => 'multiselect', 'options' => ['rrhh_software' => ['label' => 'Software de RRHH — Buk, Talana, Rex+, etc.', 'legal_purposes' => ['international_transfer']], 'seguridad_social' => ['label' => 'Seguridad social — Previred, AFP, Fonasa/Isapre (obligatorio por ley)', 'legal_purposes' => ['legal_compliance']], 'asesores_externos' => ['label' => 'Asesores externos — Contadores o abogados', 'legal_purposes' => ['contractual_execution']], 'otros' => ['label' => 'Otros (especificar)', 'legal_purposes' => ['international_transfer']], 'ninguno' => ['label' => 'Ninguno — Todo se hace internamente', 'legal_purposes' => []]], 'help_text' => 'Seleccione todas las que apliquen. La seguridad social se incluye automáticamente por obligación legal. Base legal: Art. 13 letra b (obligación legal); Art. 15 bis (mandatarios); Art. 14 bis (deber de secreto para asesores).'],
                                ['key' => 'step_3_sharing_hr_software_names', 'label' => 'Nombre del software de RRHH que utiliza', 'type' => 'text', 'show_if' => ['key' => 'step_3_sharing', 'value' => 'rrhh_software'], 'requires_purpose_selection' => true, 'help_text' => 'Ej: Buk, Talana, Rex+.'],
                                ['key' => 'step_3_sharing_other_recipients', 'label' => 'Especifique los otros destinatarios', 'type' => 'text', 'show_if' => ['key' => 'step_3_sharing', 'value' => 'otros'], 'requires_purpose_selection' => true, 'help_text' => 'Ej: Empresa de transporte, consultora de seguridad.'],
                            ],
                        ],
                    ],
                ],
                'required_condition' => ['key' => 'has_employees'],
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
                    'steps' => [
                        [
                            'title' => 'Tipo de documento',
                            'fields' => [
                                ['key' => 'custom_policy_title', 'label' => '¿Cómo se llama este documento?', 'type' => 'text', 'help_text' => 'Ej: Términos y Condiciones, Política de Devoluciones, Aviso de Privacidad para Concursos.'],
                                ['key' => 'custom_policy_is_privacy_related', 'label' => '¿Es un aviso de privacidad bajo la Ley 21.719?', 'type' => 'boolean', 'help_text' => 'Si marca "Sí", se genera un documento con la estructura obligatoria de la ley (finalidades, base de licitud, derechos ARCO+P). Si marca "No", podrá escribir el contenido libremente. Base legal: Art. 14 ter exige contenido mínimo para avisos de privacidad.'],
                            ],
                        ],
                        [
                            'title' => 'Redacción libre',
                            'fields' => [
                                ['key' => 'custom_policy_free_text_html', 'label' => 'Escriba el contenido del documento', 'type' => 'text', 'show_if' => ['key' => 'custom_policy_is_privacy_related', 'value' => false], 'help_text' => 'Puede usar formato HTML para títulos, listas y negritas. Este texto se insertará directamente en el documento.'],
                            ],
                        ],
                        [
                            'title' => 'Datos del aviso de privacidad',
                            'fields' => [
                                ['key' => 'custom_policy_context', 'label' => '¿Para qué proceso específico usa estos datos?', 'type' => 'text', 'show_if' => ['key' => 'custom_policy_is_privacy_related', 'value' => true], 'help_text' => 'Ej: Proceso de selección de personal, programa de fidelización, concurso. Base legal: Art. 14 ter letra a exige identificar la finalidad específica.'],
                                ['key' => 'custom_policy_data_categories', 'label' => '¿Qué tipo de datos personales solicita?', 'type' => 'text', 'show_if' => ['key' => 'custom_policy_is_privacy_related', 'value' => true], 'help_text' => 'Ej: Nombre, correo, teléfono, dirección, RUT. Base legal: Art. 14 ter letra b exige informar las categorías de datos tratados.'],
                                ['key' => 'custom_policy_purposes', 'label' => '¿Para qué usa estos datos?', 'type' => 'text', 'show_if' => ['key' => 'custom_policy_is_privacy_related', 'value' => true], 'help_text' => 'Ej: Evaluar postulantes para el cargo, enviar promociones, gestionar participación en un concurso. Base legal: Art. 14 ter letra a exige que la finalidad sea determinada, explícita y legítima.'],
                                ['key' => 'custom_policy_legal_basis', 'label' => '¿Bajo qué autorización legal trata esos datos?', 'type' => 'text', 'show_if' => ['key' => 'custom_policy_is_privacy_related', 'value' => true], 'help_text' => 'Ej: Consentimiento del titular, ejecución de un contrato, obligación legal. Base legal: Art. 13 establece las bases de licitud: consentimiento (letra a), contrato (letra c), obligación legal (letra b), interés legítimo (letra d).'],
                                ['key' => 'custom_policy_recipients', 'label' => '¿A quién más se le entregan estos datos?', 'type' => 'text', 'show_if' => ['key' => 'custom_policy_is_privacy_related', 'value' => true], 'help_text' => 'Ej: Ningún tercero, empresas del mismo grupo, proveedores de servicio. Base legal: Art. 14 ter letra c exige informar los destinatarios.'],
                                ['key' => 'custom_policy_international_transfers', 'label' => '¿Los datos se envían fuera de Chile? ¿A dónde?', 'type' => 'text', 'show_if' => ['key' => 'custom_policy_is_privacy_related', 'value' => true], 'help_text' => 'Ej: No / Servidores en EE.UU. bajo cláusulas contractuales. Base legal: Arts. 14 ter letra h y 27-28 regulan transferencias internacionales.'],
                                ['key' => 'custom_policy_retention_period', 'label' => '¿Por cuánto tiempo guarda estos datos?', 'type' => 'text', 'show_if' => ['key' => 'custom_policy_is_privacy_related', 'value' => true], 'help_text' => 'Ej: 2 años desde la última interacción, mientras dure la relación contractual. Base legal: Principio de Proporcionalidad (Art. 3 letra c) exige conservar datos solo el tiempo necesario.'],
                            ],
                        ],
                    ],
                ],
                'required_condition' => null,
                'is_active' => true,
            ]
        );
        $this->command->info('Legal Templates seeded successfully!');
    }
}

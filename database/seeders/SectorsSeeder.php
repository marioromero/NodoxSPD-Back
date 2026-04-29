<?php

namespace Database\Seeders;

use App\Models\Sector;
use Illuminate\Database\Seeder;

class SectorsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sectors = [
            'ACTIVIDADES ARTÍSTICAS, DE ENTRETENIMIENTO Y RECREATIVAS',
            'ACTIVIDADES DE ALOJAMIENTO Y DE SERVICIO DE COMIDAS',
            'ACTIVIDADES DE ATENCIÓN DE LA SALUD HUMANA Y DE ASISTENCIA SOCIAL',
            'ACTIVIDADES DE LOS HOGARES COMO EMPLEADORES',
            'ACTIVIDADES DE ORGANIZACIONES EXTRATERRITORIALES',
            'ACTIVIDADES DE SERVICIOS ADMINISTRATIVOS Y DE APOYO',
            'ACTIVIDADES FINANCIERAS Y DE SEGUROS',
            'ACTIVIDADES INMOBILIARIAS',
            'ACTIVIDADES PROFESIONALES, CIENTIFICAS Y TÉCNICAS',
            'ADMINISTRACIÓN PÚBLICA Y DEFENSA',
            'AGRICULTURA, GANADERÍA, SILVICULTURA Y PESCA',
            'COMERCIO Y REPARACIÓN DE VEHICULOS AUTOMOTORES',
            'CONSTRUCCIÓN',
            'ENSEÑANZA',
            'EXPLOTACIÓN DE MINAS Y CANTERAS',
            'INDUSTRIA MANUFACTURERA',
            'INFORMACIÓN Y COMUNICACIONES',
            'OTRAS ACTIVIDADES DE SERVICIOS',
            'SUMINISTRO DE AGUA; EVACUACIÓN DE AGUAS RESIDUALES',
            'SUMINISTRO DE ELECTRICIDAD, GAS, VAPOR Y AIRE ACONDICIONADO',
            'TRANSPORTE Y ALMACENAMIENTO',
        ];

        foreach ($sectors as $sector) {
            Sector::create(['name' => $sector]);
        }
    }
}

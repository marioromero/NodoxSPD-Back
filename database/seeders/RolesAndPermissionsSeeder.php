<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Creamos solo dos permisos de acceso general
        Permission::firstOrCreate(['name' => 'access_admin_dashboard']);
        Permission::firstOrCreate(['name' => 'access_person_dashboard']);

        // Rol: Admin de Empresa
        $companyAdmin = Role::firstOrCreate(['name' => 'company_admin']);
        $companyAdmin->givePermissionTo('access_admin_dashboard');

        // Rol: Persona
        $person = Role::firstOrCreate(['name' => 'person']);
        $person->givePermissionTo('access_person_dashboard');

        // Nota: El Super Admin no lo creamos aún.
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Company;

class CompaniesSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Company::create([
            'id' => 1,
            'user_id' => 1,
            'public_uuid' => '39ce42d3-f179-4290-8edf-2eaf01efb82b',
            'business_name' => 'Tech Solutions Latam',
            'tax_id' => 'US-123456789',
            'legal_address' => '123 Tech Boulevard, Silicon Valley, CA, USA',
            'arco_contact_email' => 'privacy@techsolutions.global',
            'legal_representative_name' => 'John Doe',
            'legal_representative_tax_id' => 'US-987654321',
            'is_foreign_entity' => true,
            'local_contact_for_foreign_entity' => '{"name":"Estudio Jurídico Pérez & Cía","rut":"77.123.456-7","email":"representacion@perezcia.cl","address":"Huérfanos 1160, Santiago"}',
            'dpo_designation_act' => null,
            'dpo_contact' => '{"name":"Roberto Gómez","email":"dpo@techsolutions.global","phone":"+15551234567"}',
            'legal_settings' => null,
            'last_impact_assessment_at' => null,
            'security_policy_version' => 1,
            'onboarding_completed_at' => '2026-04-15 03:02:56',
            'created_at' => '2026-04-15 03:02:31',
            'updated_at' => '2026-04-15 03:02:56',
        ]);
    }
}

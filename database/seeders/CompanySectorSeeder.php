<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompanySectorSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('company_sector')->insert([
            [
                'id' => 1,
                'company_id' => 1,
                'sector_id' => 1,
                'created_at' => '2026-04-15 03:02:56',
                'updated_at' => '2026-04-15 03:02:56',
            ],
            [
                'id' => 2,
                'company_id' => 1,
                'sector_id' => 2,
                'created_at' => '2026-04-15 03:02:56',
                'updated_at' => '2026-04-15 03:02:56',
            ],
        ]);
    }
}

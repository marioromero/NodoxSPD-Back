<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            RolesAndPermissionsSeeder::class,
            PlansSeeder::class,
            SectorsSeeder::class,
            UsersSeeder::class,
            CompaniesSeeder::class,
            CompanySectorSeeder::class,
            BusinessActivitiesSeeder::class,
            LegalTemplateSeeder::class,
            TriageQuestionSeeder::class,
            ConsentPurposesSeeder::class,
        ]);
    }
}

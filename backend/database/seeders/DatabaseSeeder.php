<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Demo CRM records are opt-in. Production and normal local installs start empty.
        if ((bool) env('SEED_DEMO_DATA', false)) {
            $this->call(NexioSeeder::class);
        }
        // Runs after the core dataset: registers the marketplace catalog and its initial rules.
        $this->call(PluginSeeder::class);
    }
}

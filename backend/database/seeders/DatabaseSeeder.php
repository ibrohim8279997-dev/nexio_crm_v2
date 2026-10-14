<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(NexioSeeder::class);
        // Runs after the core dataset: registers the marketplace catalog and its initial rules.
        $this->call(PluginSeeder::class);
    }
}

<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** Loads the deterministic dataset produced by seed/generate.mjs. */
class NexioSeeder extends Seeder
{
    public function run(): void
    {
        $path = base_path('database/schema/seed.sql');
        if (!is_file($path)) {
            $this->command?->warn('database/schema/seed.sql not found — generate it with `node seed/generate.mjs`.');
            return;
        }
        DB::unprepared(file_get_contents($path));
    }
}

<?php

use Database\Seeders\PluginSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /** Publish the current reviewed plugin files and their current integrity checksums. */
    public function up(): void
    {
        app(PluginSeeder::class)->run();
    }

    public function down(): void
    {
        // Published plugin catalogue data is retained on rollback.
    }
};

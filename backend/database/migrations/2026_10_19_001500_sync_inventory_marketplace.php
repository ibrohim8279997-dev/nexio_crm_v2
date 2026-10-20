<?php

use Database\Seeders\PluginSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Existing installations do not automatically rerun seeders when a new official plugin is
     * added. Run the idempotent catalog seeder during migration so Inventory appears in the
     * Super Admin marketplace immediately after deployment.
     */
    public function up(): void
    {
        app(PluginSeeder::class)->run();
    }

    public function down(): void
    {
        // Marketplace catalog rows are data and must not be deleted on rollback.
    }
};

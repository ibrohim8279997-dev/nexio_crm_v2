<?php

use Database\Seeders\PluginSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Navigation metadata is part of the reviewed manifest. Republish the official manifests so
     * their stored checksums match the deployed files and existing installations can update safely.
     */
    public function up(): void
    {
        app(PluginSeeder::class)->run();
    }

    public function down(): void
    {
        // Published catalog data is intentionally retained on rollback.
    }
};

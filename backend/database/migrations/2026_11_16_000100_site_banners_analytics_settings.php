<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * - site_banners: images the platform superadmin shows on the public landing page (file bytes live on a storage disk).
     * - site_visits: first-party page views. No IP address or user agent is stored: visitors are a keyed hash, logged-in
     *   users are linked by id. Rows older than 24 months are purged by the scheduler.
     * - platform_settings: small key → JSON value store for superadmin-tunable settings (custom plan prices).
     */
    public function up(): void
    {
        if (!Schema::hasTable('site_banners')) {
            Schema::create('site_banners', function (Blueprint $t) {
                $t->string('id')->primary();
                $t->string('title', 120)->nullable();
                $t->string('path');
                $t->string('disk', 20)->default('local');
                $t->string('mime', 60);
                $t->unsignedInteger('size');
                $t->unsignedSmallInteger('width')->nullable();
                $t->unsignedSmallInteger('height')->nullable();
                $t->string('link_url', 500)->nullable();
                $t->unsignedInteger('position')->default(0);
                $t->boolean('is_active')->default(true);
                $t->string('created_by')->nullable();
                $t->dateTime('created_at');
                $t->dateTime('updated_at');
                $t->index(['is_active', 'position']);
            });
        }

        if (!Schema::hasTable('site_visits')) {
            Schema::create('site_visits', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->dateTime('occurred_at');
                $t->char('visit_day', 10);          // YYYY-MM-DD in the analytics timezone (config nexio.analytics_tz)
                $t->unsignedTinyInteger('visit_hour');
                $t->string('path', 200);
                $t->string('visitor', 32);          // keyed hash of the user id, or of ip + user agent for anonymous visitors
                $t->string('user_id')->nullable();
                $t->string('tenant_id')->nullable();
                $t->string('referrer_host', 120)->nullable();
                $t->string('device', 10);           // desktop | mobile | tablet | app
                $t->index('visit_day');
                $t->index(['visit_day', 'path']);
                $t->index('user_id');
            });
        }

        if (!Schema::hasTable('platform_settings')) {
            Schema::create('platform_settings', function (Blueprint $t) {
                $t->string('key', 100)->primary();
                $t->text('value');
                $t->string('updated_by')->nullable();
                $t->dateTime('updated_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_settings');
        Schema::dropIfExists('site_visits');
        Schema::dropIfExists('site_banners');
    }
};

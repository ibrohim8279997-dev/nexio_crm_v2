<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pricing page data lives in the plans table, so prices, limits and captions change without touching markup.
     * New display columns: old_price (monthly, optional strikethrough), badge, note, price_from, cta, cta_url.
     * Existing subscriptions keep their own amount/seats; only the public catalogue changes.
     */
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $t) {
            if (!Schema::hasColumn('plans', 'old_price'))  $t->decimal('old_price', 10, 2)->nullable();
            if (!Schema::hasColumn('plans', 'badge'))      $t->string('badge')->nullable();
            if (!Schema::hasColumn('plans', 'note'))       $t->string('note')->nullable();
            if (!Schema::hasColumn('plans', 'price_from')) $t->boolean('price_from')->default(false);
            if (!Schema::hasColumn('plans', 'cta'))        $t->string('cta')->default('start');   // start | contact
            if (!Schema::hasColumn('plans', 'cta_url'))    $t->string('cta_url')->nullable();
        });

        $plans = [
            'starter' => [
                'tagline' => 'Для небольших команд, которые только начинают',
                'price_monthly' => 89, 'price_yearly' => 890, 'seats' => 5, 'api_requests' => 10000, 'ai_minutes' => 0, 'storage_gb' => 5,
                'badge' => null, 'note' => null, 'price_from' => 0, 'cta' => 'start',
                'features' => [
                    ['До 5 пользователей', 1], ['Контакты, лиды и сделки', 1], ['Канбан и таблицы', 1], ['Задачи и проекты', 1],
                    ['Поддержка по email', 1], ['Клиентские порталы', 0], ['Агенты', 0],
                ],
            ],
            'business' => [
                'tagline' => 'Полный набор для растущей компании',
                'price_monthly' => 199, 'price_yearly' => 1990, 'seats' => 15, 'api_requests' => 70000, 'ai_minutes' => 200, 'storage_gb' => 25,
                'badge' => 'Самый популярный', 'note' => null, 'price_from' => 0, 'cta' => 'start',
                'features' => [
                    ['До 15 пользователей', 1], ['Полная воронка продаж', 1], ['Финансы и счета', 1], ['Helpdesk и SLA', 1],
                    ['Расширенная аналитика', 1], ['Автоматизация', 1], ['API и вебхуки', 1], ['Клиентский портал', 1], ['White label', 1],
                ],
            ],
            'enterprise' => [
                'tagline' => 'Для крупных организаций и AI-продаж',
                'price_monthly' => 349, 'price_yearly' => 3490, 'seats' => 50, 'api_requests' => 150000, 'ai_minutes' => 500, 'storage_gb' => 100,
                'badge' => null, 'note' => 'Выгоден от 25 пользователей', 'price_from' => 1, 'cta' => 'contact',
                'features' => [
                    ['До 50 пользователей', 1], ['Мультивалютные прайсы', 1], ['Агенты продаж', 1], ['Голосовые звонки', 1],
                    ['Enterprise SSO', 1], ['Аудит и журналы', 1], ['SLA 99,9%', 1], ['Выделенный менеджер', 1],
                ],
            ],
        ];

        foreach ($plans as $code => $p) {
            $plan = DB::table('plans')->where('code', $code)->first();
            if (!$plan) continue;
            $features = $p['features'];
            unset($p['features']);
            DB::table('plans')->where('id', $plan->id)->update($p);

            DB::table('plan_features')->where('plan_id', $plan->id)->delete();
            foreach ($features as $i => [$label, $included]) {
                DB::table('plan_features')->insert([
                    'id' => 'pf_pr_' . $code . '_' . $i, 'plan_id' => $plan->id,
                    'label' => $label, 'included' => $included, 'position' => $i,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Prices and limits may already be referenced by billing data, so they are retained.
        Schema::table('plans', function (Blueprint $t) {
            foreach (['old_price', 'badge', 'note', 'price_from', 'cta', 'cta_url'] as $c) {
                if (Schema::hasColumn('plans', $c)) $t->dropColumn($c);
            }
        });
    }
};

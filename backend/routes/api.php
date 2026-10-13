<?php
use App\Support\H;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

// Health probe — parity with the Worker's /api/health.
Route::get('/health', function () {
    $ok = false;
    try { DB::selectOne('SELECT 1 AS ok'); $ok = true; } catch (\Throwable) { $ok = false; }
    return H::json(['status' => $ok ? 'ok' : 'degraded', 'service' => 'nexio-crm', 'time' => now()->toIso8601String()]);
});

require __DIR__.'/api_v1.php';

<?php

namespace App\Http\Controllers;

use App\Services\BotsSync;
use App\Services\TelegramLinks;
use App\Support\BotsSignature;
use App\Support\BotsTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Server-to-server surface under /api/bots. Every request must carry a valid HMAC signature
 * (see BotsSignature); on any other server role the routes do not exist.
 */
class BotsSyncController
{
    private function guard(Request $request, string $role): ?JsonResponse
    {
        if (config('bots.role') !== $role) return response()->json(['error' => 'not_found'], 404);
        if (!BotsSignature::verify($request)) return response()->json(['error' => 'unauthorized'], 401);
        return null;
    }

    private function json(array $data): JsonResponse
    {
        return response()->json($data, 200, [], JSON_INVALID_UTF8_SUBSTITUTE | JSON_PRESERVE_ZERO_FRACTION);
    }

    // ── site ────────────────────────────────────────────────────────────────────────────

    /** Table list with a cheap change fingerprint (row count + newest updated_at/created_at). */
    public function tables(Request $request)
    {
        if ($denied = $this->guard($request, 'site')) return $denied;
        $out = [];
        foreach (BotsTables::all() as $name) {
            $stamp = collect(['updated_at', 'created_at'])->first(fn ($c) => Schema::hasColumn($name, $c));
            $row = DB::table($name)->selectRaw('COUNT(*) AS c' . ($stamp ? ", MAX({$stamp}) AS m" : ''))->first();
            $out[] = ['name' => $name, 'count' => (int) $row->c, 'fp' => $row->c . '|' . ($row->m ?? '')];
        }
        return $this->json(['tables' => $out]);
    }

    /** One page of a table. Tables with an `id` are paged by id cursor, others by offset. */
    public function rows(Request $request, string $table)
    {
        if ($denied = $this->guard($request, 'site')) return $denied;
        if (!in_array($table, BotsTables::all(), true)) return response()->json(['error' => 'not_found'], 404);

        $limit = min(2000, max(1, (int) $request->query('limit', config('bots.page', 1000))));
        $q = DB::table($table);
        if (Schema::hasColumn($table, 'id')) {
            $q->orderBy('id');
            if (($after = $request->query('after')) !== null) $q->where('id', '>', $after);
            $rows = $q->limit($limit + 1)->get()->map(fn ($r) => (array) $r)->all();
            $more = count($rows) > $limit;
            $rows = array_slice($rows, 0, $limit);
            return $this->json(['rows' => $rows, 'next' => $more ? (string) end($rows)['id'] : null]);
        }
        $offset = max(0, (int) $request->query('offset', 0));
        $rows = $q->orderByRaw('1')->offset($offset)->limit($limit + 1)->get()->map(fn ($r) => (array) $r)->all();
        $more = count($rows) > $limit;
        return $this->json(['rows' => array_slice($rows, 0, $limit), 'next' => $more ? $offset + $limit : null]);
    }

    public function telegramLink(Request $request)
    {
        if ($denied = $this->guard($request, 'site')) return $denied;
        $token = (string) $request->input('token');
        $chatId = (string) $request->input('chat_id');
        if (!preg_match('/^[A-Za-z0-9_-]{16,80}$/', $token) || !preg_match('/^-?\d{1,20}$/', $chatId)) return response()->json(['error' => 'bad_request'], 422);
        $username = $request->input('username');
        return TelegramLinks::bind($token, $chatId, is_string($username) ? mb_substr($username, 0, 64) : null)
            ? $this->json(['ok' => true]) : response()->json(['error' => 'link_expired'], 404);
    }

    public function telegramUnlink(Request $request)
    {
        if ($denied = $this->guard($request, 'site')) return $denied;
        $chatId = (string) $request->input('chat_id');
        if (!preg_match('/^-?\d{1,20}$/', $chatId)) return response()->json(['error' => 'bad_request'], 422);
        TelegramLinks::unbind($chatId);
        return $this->json(['ok' => true]);
    }

    // ── bot server ──────────────────────────────────────────────────────────────────────

    /** The site announces changed tables; they are pulled after the response so the site's push never waits. */
    public function notify(Request $request)
    {
        if ($denied = $this->guard($request, 'bot')) return $denied;
        $tables = array_values(array_filter((array) $request->input('tables', []), fn ($t) => is_string($t) && preg_match('/^[A-Za-z0-9_]{1,64}$/', $t)));
        if ($tables) app()->terminating(fn () => (new BotsSync())->pull($tables));
        return response()->json(['ok' => true], 202);
    }
}

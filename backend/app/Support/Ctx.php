<?php
namespace App\Support;

use Illuminate\Http\Request;

/** Per-request, tenant-scoped context. Tenant identity always comes from the session. */
class Ctx
{
    public ?array $user = null;
    public ?string $tenantId = null;
    public ?string $role = null;
    public ?string $sessionId = null;
    /** @var array<string,string> */
    public array $params = [];

    public function __construct(public Request $request) {}

    public function query(?string $key = null, mixed $default = null): mixed
    {
        return $key === null ? $this->request->query() : $this->request->query($key, $default);
    }

    public function auth(): array
    {
        if (!$this->user) {
            throw new ApiException(401, 'Требуется авторизация', 'unauthorized');
        }
        return $this->user;
    }
}

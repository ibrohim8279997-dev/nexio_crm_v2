<?php
namespace App\Http\Controllers\Api\V1;

use App\Support\ApiException;
use App\Support\Ctx;
use App\Support\H;
use Illuminate\Support\Facades\DB;

/** Project team membership and the project discussion thread. Tenant-scoped; users must be active members of the company. */
class ProjectCollabController
{
    private function project(Ctx $ctx): object
    {
        $ctx->auth(); H::requireTenant($ctx);
        $p = DB::table('projects')->where('id', $ctx->params['id'])->where('tenant_id', $ctx->tenantId)->first();
        if (!$p) throw new ApiException(404, 'Проект не найден', 'not_found');
        return $p;
    }

    public function members(Ctx $ctx)
    {
        $p = $this->project($ctx); H::requirePermission($ctx, 'crm.read');
        $rows = DB::table('project_members as m')->join('users as u', 'u.id', '=', 'm.user_id')
            ->where('m.project_id', $p->id)->where('m.tenant_id', $ctx->tenantId)->orderBy('m.created_at')
            ->get(['m.user_id', 'u.name', 'u.email', 'u.avatar_color', 'm.role', 'm.created_at']);
        return H::json(['data' => $rows]);
    }

    public function addMember(Ctx $ctx)
    {
        $p = $this->project($ctx); H::requirePermission($ctx, 'projects.write');
        $b = H::body($ctx);
        $uid = H::memberRef($ctx, $b['user_id'] ?? null, 'сотрудник');
        if (!$uid) throw new ApiException(400, 'Поле «user_id» обязательно', 'bad_request');
        $role = H::oneOf($b['role'] ?? null, 'role', ['lead', 'member', 'viewer'], 'member');
        // Upsert keeps the (project, user) pair unique even under concurrent adds.
        DB::table('project_members')->upsert(
            [['id' => H::newId('pjm'), 'tenant_id' => $ctx->tenantId, 'project_id' => $p->id, 'user_id' => $uid, 'role' => $role, 'created_at' => H::now()]],
            ['project_id', 'user_id'], ['role']
        );
        H::audit($ctx, 'project.member_added', 'project', $p->id, ['user_id' => $uid, 'role' => $role]);
        return H::json(['ok' => true], 201);
    }

    public function removeMember(Ctx $ctx)
    {
        $p = $this->project($ctx); H::requirePermission($ctx, 'projects.write');
        $n = DB::table('project_members')->where('project_id', $p->id)->where('tenant_id', $ctx->tenantId)->where('user_id', $ctx->params['userId'])->delete();
        if (!$n) throw new ApiException(404, 'Участник не найден', 'not_found');
        H::audit($ctx, 'project.member_removed', 'project', $p->id, ['user_id' => $ctx->params['userId']]);
        return H::json(['ok' => true]);
    }

    public function comments(Ctx $ctx)
    {
        $p = $this->project($ctx); H::requirePermission($ctx, 'crm.read');
        $rows = DB::table('project_comments')->where('project_id', $p->id)->where('tenant_id', $ctx->tenantId)->orderBy('created_at')->get();
        return H::json(['data' => $rows]);
    }

    public function addComment(Ctx $ctx)
    {
        $p = $this->project($ctx); H::requirePermission($ctx, 'tasks.write');
        $user = $ctx->auth();
        $id = H::newId('pjc');
        DB::table('project_comments')->insert([
            'id' => $id, 'tenant_id' => $ctx->tenantId, 'project_id' => $p->id,
            'body' => H::str(H::body($ctx)['body'] ?? null, 'body', true, 4000),
            'author_id' => $user['id'], 'author_name' => $user['name'], 'created_at' => H::now(),
        ]);
        return H::json(['id' => $id], 201);
    }

    /** Authors may delete their own comments; project managers (projects.write) may delete any. */
    public function deleteComment(Ctx $ctx)
    {
        $p = $this->project($ctx);
        $user = $ctx->auth();
        $c = DB::table('project_comments')->where('id', $ctx->params['commentId'])->where('project_id', $p->id)->where('tenant_id', $ctx->tenantId)->first();
        if (!$c) throw new ApiException(404, 'Комментарий не найден', 'not_found');
        if ($c->author_id !== $user['id']) H::requirePermission($ctx, 'projects.write');
        DB::table('project_comments')->where('id', $c->id)->delete();
        return H::json(['ok' => true]);
    }
}

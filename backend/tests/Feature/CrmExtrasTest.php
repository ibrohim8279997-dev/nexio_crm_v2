<?php
namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/** Custom fields, notes, attachments, contact↔company links, time entries and task dependencies — incl. tenant isolation. */
class CrmExtrasTest extends PluginTestCase
{
    private function contact(string $as = 'alpha'): string
    {
        return $this->as($as)->postJson('/api/v1/contacts', ['first_name' => 'Ali'])->assertStatus(201)->json('id');
    }

    private function org(string $as = 'alpha', string $name = 'Acme'): string
    {
        return $this->as($as)->postJson('/api/v1/organizations', ['name' => $name])->assertStatus(201)->json('id');
    }

    private function task(string $title): string
    {
        return $this->actingAsAlpha()->postJson('/api/v1/tasks', ['title' => $title])->assertStatus(201)->json('id');
    }

    private function as(string $who): static
    {
        return $who === 'beta' ? $this->actingAsBeta() : $this->actingAsAlpha();
    }

    // ── custom fields ────────────────────────────────────────────────────────────────
    public function test_custom_fields_are_defined_validated_stored_and_isolated(): void
    {
        $fid = $this->actingAsAlpha()->postJson('/api/v1/custom-fields', ['entity' => 'contact', 'label' => 'Employees', 'type' => 'number', 'is_required' => true])
            ->assertStatus(201)->json('id');
        $sel = $this->actingAsAlpha()->postJson('/api/v1/custom-fields', ['entity' => 'contact', 'label' => 'Tier', 'type' => 'select', 'options' => ['A', 'B']])
            ->assertStatus(201)->json('id');
        $cid = $this->contact();

        $this->actingAsAlpha()->putJson("/api/v1/custom-fields/values/contact/{$cid}", ['values' => [$fid => 'many']])->assertStatus(422);
        $this->actingAsAlpha()->putJson("/api/v1/custom-fields/values/contact/{$cid}", ['values' => [$fid => 12, $sel => 'Z']])->assertStatus(422);
        $this->actingAsAlpha()->putJson("/api/v1/custom-fields/values/contact/{$cid}", ['values' => [$sel => 'A']])->assertStatus(422); // required number missing
        $this->actingAsAlpha()->putJson("/api/v1/custom-fields/values/contact/{$cid}", ['values' => [$fid => 12, $sel => 'A']])->assertStatus(200);

        $got = $this->actingAsAlpha()->getJson("/api/v1/custom-fields/values/contact/{$cid}")->assertStatus(200)->json();
        $this->assertSame('12', $got['values'][$fid]);
        $this->assertSame('A', $got['values'][$sel]);

        // Company B sees neither the definitions nor the record.
        $this->assertSame([], $this->actingAsBeta()->getJson('/api/v1/custom-fields?entity=contact')->json('data'));
        $this->actingAsBeta()->getJson("/api/v1/custom-fields/values/contact/{$cid}")->assertStatus(404);
        $this->actingAsBeta()->putJson("/api/v1/custom-fields/values/contact/{$cid}", ['values' => [$fid => 1]])->assertStatus(404);
        $this->actingAsBeta()->deleteJson("/api/v1/custom-fields/{$fid}")->assertStatus(404);
    }

    // ── notes ────────────────────────────────────────────────────────────────────────
    public function test_notes_are_scoped_to_the_company(): void
    {
        $cid = $this->contact();
        $nid = $this->actingAsAlpha()->postJson('/api/v1/notes', ['entity_type' => 'contact', 'entity_id' => $cid, 'body' => 'Call back Monday'])->assertStatus(201)->json('id');
        $this->assertCount(1, $this->actingAsAlpha()->getJson("/api/v1/notes?entity_type=contact&entity_id={$cid}")->json('data'));

        $this->actingAsBeta()->getJson("/api/v1/notes?entity_type=contact&entity_id={$cid}")->assertStatus(404);
        $this->actingAsBeta()->postJson('/api/v1/notes', ['entity_type' => 'contact', 'entity_id' => $cid, 'body' => 'x'])->assertStatus(404);
        $this->actingAsBeta()->deleteJson("/api/v1/notes/{$nid}")->assertStatus(404);
        $this->actingAsAlpha()->postJson('/api/v1/notes', ['entity_type' => 'bogus', 'entity_id' => $cid, 'body' => 'x'])->assertStatus(400);

        $this->actingAsAlpha()->deleteJson("/api/v1/notes/{$nid}")->assertStatus(200);
    }

    // ── attachments ──────────────────────────────────────────────────────────────────
    public function test_new_uploads_go_to_the_configured_disk_while_old_files_stay_readable(): void
    {
        Storage::fake('local');
        Storage::fake('s3');
        $cid = $this->contact();
        $up = fn () => $this->actingAsAlpha()->post('/api/v1/attachments', [
            'entity_type' => 'contact', 'entity_id' => $cid, 'file' => UploadedFile::fake()->createWithContent('a.pdf', '%PDF-1.4 x'),
        ], ['Accept' => 'application/json'])->assertStatus(201)->json('id');

        $old = $up();                                         // default disk
        config(['filesystems.attachments_disk' => 's3']);
        $new = $up();                                         // after switching to S3

        $this->assertSame('local', DB::table('attachments')->where('id', $old)->value('disk'));
        $this->assertSame('s3', DB::table('attachments')->where('id', $new)->value('disk'));
        Storage::disk('s3')->assertExists("tenants/tnt_a/attachments/{$new}");
        Storage::disk('local')->assertExists("tenants/tnt_a/attachments/{$old}");
        $this->actingAsAlpha()->get("/api/v1/attachments/{$old}/download")->assertStatus(200);
        $this->actingAsAlpha()->get("/api/v1/attachments/{$new}/download")->assertStatus(200);
        $this->actingAsAlpha()->deleteJson("/api/v1/attachments/{$new}")->assertOk();
        Storage::disk('s3')->assertMissing("tenants/tnt_a/attachments/{$new}");
    }

    public function test_attachments_upload_download_and_isolation(): void
    {
        Storage::fake('local');
        $cid = $this->contact();
        $res = $this->actingAsAlpha()->post('/api/v1/attachments', [
            'entity_type' => 'contact', 'entity_id' => $cid, 'file' => UploadedFile::fake()->createWithContent('../../evil name.pdf', "%PDF-1.4 hello"),
        ], ['Accept' => 'application/json'])->assertStatus(201);
        $aid = $res->json('id');

        $row = DB::table('attachments')->where('id', $aid)->first();
        $this->assertSame("tenants/tnt_a/attachments/{$aid}", $row->path, 'storage path must be built from ids only');
        $this->assertStringNotContainsString('/', $row->name);
        Storage::disk('local')->assertExists($row->path);

        $dl = $this->actingAsAlpha()->get("/api/v1/attachments/{$aid}/download")->assertStatus(200);
        $this->assertSame('application/octet-stream', $dl->headers->get('Content-Type'));
        $this->assertSame('nosniff', $dl->headers->get('X-Content-Type-Options'));

        $this->actingAsBeta()->get("/api/v1/attachments/{$aid}/download")->assertStatus(404);
        $this->actingAsBeta()->deleteJson("/api/v1/attachments/{$aid}")->assertStatus(404);
        $this->actingAsBeta()->getJson("/api/v1/attachments?entity_type=contact&entity_id={$cid}")->assertStatus(404);

        $this->actingAsAlpha()->post('/api/v1/attachments', [
            'entity_type' => 'contact', 'entity_id' => $cid, 'file' => UploadedFile::fake()->createWithContent('run.exe', 'MZ'),
        ], ['Accept' => 'application/json'])->assertStatus(415);
        $this->actingAsAlpha()->post('/api/v1/attachments', [
            'entity_type' => 'contact', 'entity_id' => $cid, 'file' => UploadedFile::fake()->create('big.pdf', 11 * 1024),
        ], ['Accept' => 'application/json'])->assertStatus(413);

        $this->actingAsAlpha()->deleteJson("/api/v1/attachments/{$aid}")->assertStatus(200);
        Storage::disk('local')->assertMissing($row->path);
    }

    public function test_attachment_quota_of_the_plan_is_enforced(): void
    {
        Storage::fake('local');
        DB::table('plans')->where('id', 'plan_business')->update(['storage_gb' => 0.000001]); // ~1 KB
        $cid = $this->contact();
        $this->actingAsAlpha()->post('/api/v1/attachments', [
            'entity_type' => 'contact', 'entity_id' => $cid, 'file' => UploadedFile::fake()->create('a.pdf', 50),
        ], ['Accept' => 'application/json'])->assertStatus(402);
    }

    // ── contact ↔ company ────────────────────────────────────────────────────────────
    public function test_a_contact_can_belong_to_several_companies_with_roles(): void
    {
        $cid = $this->contact();
        $o1 = $this->org('alpha', 'Acme'); $o2 = $this->org('alpha', 'Globex');
        $this->actingAsAlpha()->postJson("/api/v1/contacts/{$cid}/organizations", ['org_id' => $o1, 'role' => 'CEO', 'is_primary' => true])->assertStatus(201);
        $this->actingAsAlpha()->postJson("/api/v1/contacts/{$cid}/organizations", ['org_id' => $o2, 'role' => 'Advisor'])->assertStatus(201);

        $detail = $this->actingAsAlpha()->getJson("/api/v1/contacts/{$cid}")->assertStatus(200)->json();
        $this->assertCount(2, $detail['organizations']);
        $this->assertSame($o1, $detail['org_id'], 'primary link updates the contact\'s main company');

        $names = array_column($this->actingAsAlpha()->getJson("/api/v1/organizations/{$o2}")->json('contacts'), 'id');
        $this->assertContains($cid, $names, 'linked contact appears on the second company too');

        $foreign = $this->org('beta', 'Foreign');
        $this->actingAsAlpha()->postJson("/api/v1/contacts/{$cid}/organizations", ['org_id' => $foreign])->assertStatus(422);
        $this->actingAsBeta()->postJson("/api/v1/contacts/{$cid}/organizations", ['org_id' => $foreign])->assertStatus(404);

        $this->actingAsAlpha()->deleteJson("/api/v1/contacts/{$cid}/organizations/{$o2}")->assertStatus(200);
        $this->assertCount(1, $this->actingAsAlpha()->getJson("/api/v1/contacts/{$cid}")->json('organizations'));
    }

    // ── time entries & dependencies ──────────────────────────────────────────────────
    public function test_time_entries_accumulate_and_validate(): void
    {
        $tid = $this->task('Design');
        $this->actingAsAlpha()->postJson("/api/v1/tasks/{$tid}/time", ['minutes' => 90, 'note' => 'mockups'])->assertStatus(201);
        $this->actingAsAlpha()->postJson("/api/v1/tasks/{$tid}/time", ['minutes' => 30])->assertStatus(201);
        $this->actingAsAlpha()->postJson("/api/v1/tasks/{$tid}/time", ['minutes' => 0])->assertStatus(400);
        $this->actingAsAlpha()->postJson("/api/v1/tasks/{$tid}/time", ['minutes' => 5000])->assertStatus(400);

        $list = $this->actingAsAlpha()->getJson("/api/v1/tasks/{$tid}/time")->assertStatus(200)->json();
        $this->assertSame(120, $list['total_minutes']);
        $this->actingAsBeta()->getJson("/api/v1/tasks/{$tid}/time")->assertStatus(404);
        $this->actingAsBeta()->postJson("/api/v1/tasks/{$tid}/time", ['minutes' => 10])->assertStatus(404);
    }

    public function test_task_dependencies_block_work_and_reject_cycles(): void
    {
        $a = $this->task('A'); $b = $this->task('B'); $c = $this->task('C');
        $this->actingAsAlpha()->postJson("/api/v1/tasks/{$b}/dependencies", ['depends_on_id' => $a])->assertStatus(201); // B waits for A
        $this->actingAsAlpha()->postJson("/api/v1/tasks/{$c}/dependencies", ['depends_on_id' => $b])->assertStatus(201); // C waits for B

        $this->actingAsAlpha()->postJson("/api/v1/tasks/{$a}/dependencies", ['depends_on_id' => $c])->assertStatus(422)->assertJsonPath('error.code', 'dependency_cycle');
        $this->actingAsAlpha()->postJson("/api/v1/tasks/{$a}/dependencies", ['depends_on_id' => $a])->assertStatus(422);

        $this->actingAsAlpha()->postJson("/api/v1/tasks/{$b}/status", ['status' => 'done'])->assertStatus(409)->assertJsonPath('error.code', 'blocked');
        $this->actingAsAlpha()->postJson("/api/v1/tasks/{$b}/status", ['status' => 'in_progress'])->assertStatus(409);

        $this->actingAsAlpha()->postJson("/api/v1/tasks/{$a}/status", ['status' => 'done'])->assertStatus(200);
        $this->actingAsAlpha()->postJson("/api/v1/tasks/{$b}/status", ['status' => 'done'])->assertStatus(200);

        $deps = $this->actingAsAlpha()->getJson("/api/v1/tasks/{$b}/dependencies")->json();
        $this->assertCount(1, $deps['blocked_by']);
        $this->assertCount(1, $deps['blocks']);

        $this->actingAsBeta()->postJson("/api/v1/tasks/{$b}/dependencies", ['depends_on_id' => $a])->assertStatus(404);
        $this->actingAsAlpha()->deleteJson("/api/v1/tasks/{$c}/dependencies/{$deps['blocks'][0]['link_id']}")->assertStatus(200);
    }
}

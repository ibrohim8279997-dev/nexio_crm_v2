<?php
namespace Tests\Feature;

use App\Support\AiDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/** Real document files, AI draft gaps, and the company's single AI → CRM API key. */
class DocumentsAndAiAccessTest extends PluginTestCase
{
    public function test_ai_gaps_are_parsed_once_in_order(): void
    {
        $this->assertSame(['narx', 'muddat'], AiDocument::missing('A [MISSING: narx] B [MISSING: muddat] C [MISSING: narx]'));
        $this->assertSame([], AiDocument::missing('no gaps'));
    }

    public function test_document_file_upload_download_isolation_and_delete(): void
    {
        Storage::fake('local');
        $file = UploadedFile::fake()->create('offer.pdf', 50, 'application/pdf');
        $id = $this->actingAsAlpha()->post('/api/v1/documents/upload', ['file' => $file, 'name' => 'Offer', 'kind' => 'proposal'])
            ->assertStatus(201)->json('id');

        $row = collect($this->actingAsAlpha()->getJson('/api/v1/documents')->json('data'))->firstWhere('id', $id);
        $this->assertSame(1, (int) $row['has_file']);
        $this->assertSame('offer.pdf', $row['original_name']);

        $this->actingAsAlpha()->get("/api/v1/documents/{$id}/file")->assertStatus(200);
        $this->actingAsBeta()->get("/api/v1/documents/{$id}/file")->assertStatus(404);
        $this->actingAsBeta()->deleteJson("/api/v1/documents/{$id}")->assertStatus(404);

        $bad = UploadedFile::fake()->create('shell.php', 1, 'application/x-php');
        $this->actingAsAlpha()->post('/api/v1/documents/upload', ['file' => $bad])->assertStatus(415);

        $this->actingAsAlpha()->deleteJson("/api/v1/documents/{$id}")->assertStatus(200);
        $this->actingAsAlpha()->get("/api/v1/documents/{$id}/file")->assertStatus(404);
    }

    public function test_ai_crm_key_is_generated_once_and_dies_with_the_key(): void
    {
        $this->assertFalse($this->actingAsAlpha()->getJson('/api/v1/ai/crm-access')->assertStatus(200)->json('connected'));

        $res = $this->actingAsAlpha()->postJson('/api/v1/ai/crm-access/generate')->assertStatus(201);
        $this->assertStringStartsWith('nxk_', $res->json('token'));
        $this->assertTrue($res->json('connected'));
        $keyId = $res->json('key_id');

        // One key per company: a second setup is refused until the first is disconnected.
        $this->actingAsAlpha()->postJson('/api/v1/ai/crm-access/generate')->assertStatus(409);

        // Revoking the key in "API keys" cuts the AI off at once.
        $this->actingAsAlpha()->deleteJson("/api/v1/api-keys/{$keyId}")->assertStatus(200);
        $st = $this->actingAsAlpha()->getJson('/api/v1/ai/crm-access')->json();
        $this->assertFalse($st['connected']);
        $this->assertSame('revoked', $st['status']);

        $this->actingAsAlpha()->deleteJson('/api/v1/ai/crm-access')->assertStatus(200);
        $this->assertSame('not_connected', $this->actingAsAlpha()->getJson('/api/v1/ai/crm-access')->json('status'));
    }

    public function test_pasting_a_key_checks_ownership_and_scope(): void
    {
        $mine = $this->actingAsAlpha()->postJson('/api/v1/api-keys', ['name' => 'k', 'scopes' => ['crm.read']])->assertStatus(201)->json('token');
        $theirs = $this->actingAsBeta()->postJson('/api/v1/api-keys', ['name' => 'k', 'scopes' => ['crm.read']])->assertStatus(201)->json('token');
        $writeOnly = $this->actingAsAlpha()->postJson('/api/v1/api-keys', ['name' => 'w', 'scopes' => ['crm.write']])->assertStatus(201)->json('token');

        $this->actingAsAlpha()->postJson('/api/v1/ai/crm-access', ['token' => $theirs])->assertStatus(422);
        $this->actingAsAlpha()->postJson('/api/v1/ai/crm-access', ['token' => $writeOnly])->assertStatus(422);
        $this->actingAsAlpha()->postJson('/api/v1/ai/crm-access', ['token' => 'nope'])->assertStatus(422);
        $this->assertTrue($this->actingAsAlpha()->postJson('/api/v1/ai/crm-access', ['token' => $mine])->assertStatus(200)->json('connected'));
    }
}

<?php

namespace App\Console\Commands;

use App\Support\H;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/** Anonymises accounts whose 30-day deletion grace period (AuthController::deleteAccount) has passed. */
class PurgeDeletedAccounts extends Command
{
    protected $signature = 'nexio:accounts:purge {--days=30 : grace period}';
    protected $description = 'Anonymise user accounts that requested deletion more than N days ago';

    public function handle(): int
    {
        $cutoff = gmdate('Y-m-d H:i:s', time() - 86400 * max(1, (int) $this->option('days')));
        $ids = DB::table('users')->whereNotNull('deletion_requested_at')->where('deletion_requested_at', '<=', $cutoff)->pluck('id');
        foreach ($ids as $id) {
            DB::transaction(function () use ($id) {
                DB::table('users')->where('id', $id)->update([
                    'email' => "deleted-{$id}@deleted.invalid", 'name' => 'Deleted user',
                    'password_hash' => H::hashPassword(H::newToken()), 'mfa_enabled' => 0, 'mfa_secret' => null, 'mfa_recovery' => null,
                    'email_verify_token' => null, 'deletion_requested_at' => null, 'updated_at' => H::now(),
                ]);
                DB::table('tenant_memberships')->where('user_id', $id)->update(['status' => 'suspended']);
                DB::table('sessions')->where('user_id', $id)->delete();
                DB::table('device_tokens')->where('user_id', $id)->delete();
                DB::table('site_visits')->where('user_id', $id)->delete();
                DB::table('user_telegram_links')->where('user_id', $id)->delete();
                DB::table('audit_logs')->insert([
                    'id' => H::newId('aud'), 'tenant_id' => null, 'actor_id' => $id, 'actor_name' => 'Deleted user',
                    'action' => 'account.purged', 'target' => 'user', 'created_at' => H::now(),
                ]);
            });
        }
        $this->info(count($ids) . ' account(s) anonymised');
        return self::SUCCESS;
    }
}

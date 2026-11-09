<?php
namespace App\Policies;

use App\Support\Rbac;

/**
 * Who may change a quote, and when. The "user" is the plain object built from the request Ctx
 * (id, role, tenant_id, is_platform_admin) because the CRM authenticates with its own session layer.
 */
class QuotePolicy
{
    /** Allowed next statuses; anything else is an illegal transition. */
    public const TRANSITIONS = [
        'draft' => ['sent', 'expired'],
        'sent' => ['accepted', 'rejected', 'expired'],
        'accepted' => [], 'rejected' => [], 'expired' => [],
    ];

    public function view(object $user, object $quote): bool
    {
        return $quote->tenant_id === $user->tenant_id && ($user->is_platform_admin || Rbac::can($user->role, 'crm.read'));
    }

    public function update(object $user, object $quote): bool
    {
        return $quote->tenant_id === $user->tenant_id
            && ($user->is_platform_admin || Rbac::can($user->role, 'deals.write'))
            && $quote->status === 'draft';
    }

    public function transition(object $user, object $quote, string $to): bool
    {
        return $quote->tenant_id === $user->tenant_id
            && ($user->is_platform_admin || Rbac::can($user->role, 'deals.write'))
            && in_array($to, self::TRANSITIONS[$quote->status] ?? [], true);
    }

    public function convert(object $user, object $quote): bool
    {
        return $quote->tenant_id === $user->tenant_id
            && ($user->is_platform_admin || Rbac::can($user->role, 'orders.write'))
            && $quote->status === 'accepted' && empty($quote->order_id);
    }
}

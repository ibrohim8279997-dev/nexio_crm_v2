<?php
use App\Http\Controllers\Api\V1\AdminMarketplaceController;
use App\Http\Controllers\Api\V1\AiController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BillingController;
use App\Http\Controllers\Api\V1\CrmController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\DeveloperController;
use App\Http\Controllers\Api\V1\MarketplaceController;
use App\Http\Controllers\Api\V1\ModulesController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\OpsController;
use App\Http\Controllers\Api\V1\PlatformController;
use App\Http\Controllers\Api\V1\PluginManagerController;
use App\Http\Controllers\Api\V1\PluginOperationsController;
use App\Http\Controllers\Api\V1\PortalController;
use App\Http\Controllers\Api\V1\SalesController;
use App\Http\Controllers\Api\V1\TeamController;
use App\Support\Ctx;
use Illuminate\Support\Facades\Route;

/** Binds the controller method with the resolved Ctx (named route parameters merged in). */
$h = fn (string $controller, string $method) => function (\Illuminate\Http\Request $request) use ($controller, $method) {
    $ctx = app(Ctx::class);
    $ctx->params = $request->route()?->parameters() ?? [];
    return app($controller)->{$method}($ctx);
};

Route::prefix('v1')->group(function () use ($h) {
    // ── auth ────────────────────────────────────────────────────────────────
    Route::post('/auth/register', $h(AuthController::class, 'register'));
    Route::post('/auth/login', $h(AuthController::class, 'login'));
    Route::post('/auth/logout', $h(AuthController::class, 'logout'));
    Route::get('/auth/me', $h(AuthController::class, 'me'));
    Route::post('/auth/switch-tenant', $h(AuthController::class, 'switchTenant'));
    Route::post('/auth/forgot-password', $h(AuthController::class, 'forgotPassword'));
    Route::post('/auth/reset-password', $h(AuthController::class, 'resetPassword'));
    Route::get('/invites/{token}', $h(AuthController::class, 'inviteInfo'));
    Route::post('/invites/{token}/accept', $h(AuthController::class, 'acceptInvite'));

    // ── dashboard, analytics, search ───────────────────────────────────────
    Route::get('/dashboard', $h(DashboardController::class, 'summary'));
    Route::get('/notifications', $h(NotificationController::class, 'index'));
    Route::post('/notifications/read-all', $h(NotificationController::class, 'readAll'));
    Route::post('/notifications/telegram-link', $h(NotificationController::class, 'telegramLink'));
    Route::post('/notifications/{id}/read', $h(NotificationController::class, 'read'));
    Route::get('/dashboard/workpanel', $h(DashboardController::class, 'workpanel'));
    Route::get('/dashboard/my-day', $h(DashboardController::class, 'myDay'));
    Route::get('/analytics', $h(DashboardController::class, 'analytics'));
    Route::get('/search', $h(DashboardController::class, 'search'));

    // ── CRM ────────────────────────────────────────────────────────────────
    Route::get('/organizations', $h(CrmController::class, 'listOrganizations'));
    Route::post('/organizations', $h(CrmController::class, 'createOrganization'));
    Route::get('/organizations/{id}', $h(CrmController::class, 'getOrganization'));
    Route::patch('/organizations/{id}', $h(CrmController::class, 'updateOrganization'));
    Route::delete('/organizations/{id}', $h(CrmController::class, 'deleteOrganization'));
    Route::get('/contacts', $h(CrmController::class, 'listContacts'));
    Route::post('/contacts', $h(CrmController::class, 'createContact'));
    Route::patch('/contacts/{id}', $h(CrmController::class, 'updateContact'));
    Route::delete('/contacts/{id}', $h(CrmController::class, 'deleteContact'));
    Route::get('/leads', $h(SalesController::class, 'listLeads'));
    Route::post('/leads', $h(SalesController::class, 'createLead'));
    Route::patch('/leads/{id}', $h(SalesController::class, 'updateLead'));
    Route::post('/leads/{id}/convert', $h(SalesController::class, 'convertLead'));
    Route::post('/leads/{id}/disqualify', $h(SalesController::class, 'disqualifyLead'));
    Route::get('/pipeline', $h(SalesController::class, 'pipeline'));
    Route::get('/deals', $h(SalesController::class, 'listDeals'));
    Route::post('/deals', $h(SalesController::class, 'createDeal'));
    Route::get('/deals/{id}', $h(SalesController::class, 'getDeal'));
    Route::patch('/deals/{id}', $h(SalesController::class, 'updateDeal'));
    Route::delete('/deals/{id}', $h(SalesController::class, 'deleteDeal'));
    Route::post('/deals/{id}/move', $h(SalesController::class, 'moveDeal'));
    Route::post('/activities', $h(SalesController::class, 'addActivity'));
    Route::get('/export', $h(SalesController::class, 'exportCsv'));

    // ── orders, projects, tasks ────────────────────────────────────────────
    Route::get('/orders', $h(OpsController::class, 'listOrders'));
    Route::post('/orders', $h(OpsController::class, 'createOrder'));
    Route::get('/orders/{id}', $h(OpsController::class, 'getOrder'));
    Route::post('/orders/{id}/confirm', $h(OpsController::class, 'confirmOrder'));
    Route::post('/orders/{id}/status', $h(OpsController::class, 'updateOrderStatus'));
    Route::post('/orders/{id}/project', $h(OpsController::class, 'createProjectFromOrder'));
    Route::get('/projects', $h(OpsController::class, 'listProjects'));
    Route::post('/projects', $h(OpsController::class, 'createProject'));
    Route::get('/projects/{id}', $h(OpsController::class, 'getProject'));
    Route::patch('/projects/{id}', $h(OpsController::class, 'updateProject'));
    Route::post('/projects/{id}/milestones', $h(OpsController::class, 'addMilestone'));
    Route::post('/milestones/{id}/status', $h(OpsController::class, 'setMilestoneStatus'));
    Route::get('/tasks', $h(OpsController::class, 'listTasks'));
    Route::post('/tasks', $h(OpsController::class, 'createTask'));
    Route::patch('/tasks/{id}', $h(OpsController::class, 'updateTask'));
    Route::delete('/tasks/{id}', $h(OpsController::class, 'removeTask'));
    Route::post('/tasks/{id}/status', $h(OpsController::class, 'setTaskStatus'));

    // ── catalog, helpdesk, comms, docs, automations, integrations ─────────
    Route::get('/products', $h(ModulesController::class, 'listProducts'));
    Route::post('/products', $h(ModulesController::class, 'createProduct'));
    Route::patch('/products/{id}', $h(ModulesController::class, 'updateProduct'));
    Route::get('/tickets', $h(ModulesController::class, 'listTickets'));
    Route::post('/tickets', $h(ModulesController::class, 'createTicket'));
    Route::post('/tickets/{id}/status', $h(ModulesController::class, 'setTicketStatus'));
    Route::get('/communications', $h(ModulesController::class, 'listCommunications'));
    Route::post('/communications', $h(ModulesController::class, 'sendCommunication'));
    Route::get('/documents', $h(ModulesController::class, 'listDocuments'));
    Route::get('/documents/{id}', $h(ModulesController::class, 'getDocument'));
    Route::post('/documents', $h(ModulesController::class, 'createDocument'));
    Route::post('/documents/proposal-draft', $h(ModulesController::class, 'generateProposal'));
    Route::post('/documents/{id}/status', $h(ModulesController::class, 'setDocumentStatus'));
    Route::patch('/documents/{id}/content', $h(ModulesController::class, 'updateDocumentContent'));
    Route::get('/automations', $h(ModulesController::class, 'listAutomations'));
    Route::post('/automations', $h(ModulesController::class, 'createAutomation'));
    Route::get('/automations/dry-run', $h(ModulesController::class, 'dryRunAutomation'));
    Route::post('/automations/{id}/toggle', $h(ModulesController::class, 'toggleAutomation'));
    Route::get('/integrations', $h(ModulesController::class, 'listIntegrations'));
    Route::post('/integrations/{id}/toggle', $h(ModulesController::class, 'toggleIntegration'));
    Route::get('/nexio-services', $h(ModulesController::class, 'listNexioServices'));
    Route::post('/nexio-services', $h(ModulesController::class, 'createNexioService'));

    // ── team, roles, settings ──────────────────────────────────────────────
    Route::get('/team', $h(TeamController::class, 'listTeam'));
    Route::get('/team/permissions', $h(TeamController::class, 'permissions'));
    Route::post('/team/invite', $h(TeamController::class, 'invite'));
    Route::post('/team/invites/{id}/revoke', $h(TeamController::class, 'revokeInvite'));
    Route::patch('/team/members/{id}', $h(TeamController::class, 'updateMember'));
    Route::delete('/team/members/{id}', $h(TeamController::class, 'removeMember'));
    Route::get('/settings', $h(TeamController::class, 'getSettings'));
    Route::patch('/settings', $h(TeamController::class, 'updateSettings'));
    Route::post('/settings/stages', $h(TeamController::class, 'addStage'));
    Route::patch('/settings/stages/{id}', $h(TeamController::class, 'updateStage'));
    Route::delete('/settings/stages/{id}', $h(TeamController::class, 'removeStage'));
    Route::get('/settings/audit', $h(TeamController::class, 'auditLog'));

    // ── billing & finance ──────────────────────────────────────────────────
    Route::get('/billing/plans', $h(BillingController::class, 'plans'));
    Route::get('/billing', $h(BillingController::class, 'subscription'));
    Route::post('/billing/plan', $h(BillingController::class, 'changePlan'));
    Route::post('/billing/cancel', $h(BillingController::class, 'cancel'));
    Route::post('/billing/resume', $h(BillingController::class, 'resume'));
    Route::post('/billing/invoices/{id}/pay', $h(BillingController::class, 'payInvoice'));
    Route::get('/finance', $h(BillingController::class, 'finance'));

    // ── Nexio AI ───────────────────────────────────────────────────────────
    Route::get('/ai/agents', $h(AiController::class, 'listAgents'));
    Route::post('/ai/agents', $h(AiController::class, 'createAgent'));
    Route::get('/ai/agents/{id}', $h(AiController::class, 'getAgent'));
    Route::patch('/ai/agents/{id}', $h(AiController::class, 'updateAgent'));
    Route::post('/ai/agents/{id}/status', $h(AiController::class, 'setAgentStatus'));
    Route::post('/ai/campaigns', $h(AiController::class, 'createCampaign'));
    Route::post('/ai/campaigns/{id}/start', $h(AiController::class, 'startCampaign'));
    Route::post('/ai/campaigns/{id}/stop', $h(AiController::class, 'stopCampaign'));
    Route::post('/ai/calls/simulate', $h(AiController::class, 'simulateCall'));
    Route::get('/ai/calls', $h(AiController::class, 'listCalls'));
    Route::get('/ai/calls/{id}', $h(AiController::class, 'getCall'));
    Route::post('/ai/calls/{id}/finalize', $h(AiController::class, 'finalizeCall'));
    Route::get('/ai/approvals', $h(AiController::class, 'approvals'));
    Route::post('/ai/approvals/{id}', $h(AiController::class, 'decideApproval'));
    Route::get('/ai/suppression', $h(AiController::class, 'suppression'));
    Route::post('/ai/suppression', $h(AiController::class, 'addSuppression'));
    Route::get('/ai/analytics', $h(AiController::class, 'analytics'));
    Route::post('/ai/complete', $h(AiController::class, 'complete'));

    // ── client portal ───────────────────────────────────────────────────────
    Route::get('/portal', $h(PortalController::class, 'overview'));
    Route::post('/portal/requests', $h(PortalController::class, 'createServiceRequest'));
    Route::post('/portal/tickets', $h(PortalController::class, 'createTicket'));
    Route::post('/portal/messages', $h(PortalController::class, 'sendMessage'));

    // ── Nexio Lab platform ─────────────────────────────────────────────────
    Route::get('/platform/overview', $h(PlatformController::class, 'overview'));
    Route::get('/platform/tenants', $h(PlatformController::class, 'tenants'));
    Route::get('/platform/tenants/{id}', $h(PlatformController::class, 'tenantDetail'));
    Route::post('/platform/tenants/{id}/status', $h(PlatformController::class, 'setTenantStatus'));
    Route::get('/platform/plans', $h(PlatformController::class, 'plans'));
    Route::patch('/platform/plans/{id}', $h(PlatformController::class, 'updatePlan'));
    Route::get('/platform/subscriptions', $h(PlatformController::class, 'subscriptions'));
    Route::get('/platform/usage', $h(PlatformController::class, 'usage'));
    Route::get('/platform/service-orders', $h(PlatformController::class, 'serviceOrders'));
    Route::patch('/platform/service-orders/{id}', $h(PlatformController::class, 'updateServiceOrder'));
    Route::get('/platform/support', $h(PlatformController::class, 'support'));
    Route::patch('/platform/support/{id}', $h(PlatformController::class, 'updateSupportTicket'));
    Route::get('/platform/security', $h(PlatformController::class, 'security'));
    Route::get('/platform/ai-ops', $h(PlatformController::class, 'aiOps'));
    Route::get('/platform/settings', $h(PlatformController::class, 'settings'));
    Route::get('/platform/audit', $h(PlatformController::class, 'impersonatedAudit'));

    // ══════════════════════════════════════════════════════════════════════════════════
    // Nexio Plugin System + Marketplace
    //
    // Ordering matters: literal segments (/plugins/installed, /plugins/connections/…)
    // are registered before the /plugins/{id} wildcards so they are never shadowed.
    // ══════════════════════════════════════════════════════════════════════════════════

    // ── marketplace (catalog read, computed states) ────────────────────────────────
    Route::get('/marketplace/categories', $h(MarketplaceController::class, 'categories'));
    Route::get('/marketplace/updates', $h(MarketplaceController::class, 'updates'));
    Route::get('/marketplace/plan-access', $h(MarketplaceController::class, 'planAccess'));
    Route::get('/marketplace/activity', $h(MarketplaceController::class, 'activity'));
    Route::get('/marketplace/install-check/{id}', $h(MarketplaceController::class, 'installCheck'));
    Route::get('/marketplace/plugins', $h(MarketplaceController::class, 'plugins'));
    Route::get('/marketplace/plugins/{id}', $h(MarketplaceController::class, 'plugin'));
    Route::post('/marketplace/plugins/{id}/purchase', $h(MarketplaceController::class, 'purchase'));

    // ── plugin manager ────────────────────────────────────────────────────────────
    Route::get('/plugins/installed', $h(PluginManagerController::class, 'installed'));
    Route::get('/plugins/dead-letters', $h(PluginManagerController::class, 'deadLetters'));

    // Connections are addressed by their own id, never by the plugin id.
    Route::patch('/plugins/connections/{connectionId}', $h(PluginManagerController::class, 'updateConnection'));
    Route::delete('/plugins/connections/{connectionId}', $h(PluginManagerController::class, 'deleteConnection'));
    Route::post('/plugins/connections/{connectionId}/secrets', $h(PluginManagerController::class, 'putSecrets'));
    Route::delete('/plugins/connections/{connectionId}/secrets/{key}', $h(PluginManagerController::class, 'deleteSecret'));

    Route::post('/plugins/{id}/install', $h(PluginManagerController::class, 'install'));
    Route::post('/plugins/{id}/enable', $h(PluginManagerController::class, 'enable'));
    Route::post('/plugins/{id}/disable', $h(PluginManagerController::class, 'disable'));
    Route::post('/plugins/{id}/update', $h(PluginManagerController::class, 'update'));
    Route::post('/plugins/{id}/rollback', $h(PluginManagerController::class, 'rollback'));
    Route::delete('/plugins/{id}', $h(PluginManagerController::class, 'uninstall'));

    Route::get('/plugins/{id}/capabilities', $h(PluginManagerController::class, 'capabilities'));
    Route::get('/plugins/{id}/health', $h(PluginManagerController::class, 'health'));
    Route::get('/plugins/{id}/settings', $h(PluginManagerController::class, 'getSettings'));
    Route::put('/plugins/{id}/settings', $h(PluginManagerController::class, 'putSettings'));
    Route::get('/plugins/{id}/usage', $h(PluginManagerController::class, 'usage'));
    Route::get('/plugins/{id}/limits', $h(PluginManagerController::class, 'limits'));
    Route::get('/plugins/{id}/connections', $h(PluginManagerController::class, 'connections'));
    Route::post('/plugins/{id}/connections', $h(PluginManagerController::class, 'createConnection'));
    Route::get('/plugins/{id}/subscriptions', $h(PluginManagerController::class, 'subscriptions'));
    Route::post('/plugins/{id}/subscriptions', $h(PluginManagerController::class, 'subscribe'));
    Route::delete('/plugins/{id}/subscriptions/{event}', $h(PluginManagerController::class, 'unsubscribe'));
    Route::get('/plugins/{id}', $h(PluginManagerController::class, 'show'));

    // Generic, fully-guarded plugin operation dispatch.
    Route::post('/plugins/{id}/operations/{operation}', $h(PluginOperationsController::class, 'invoke'));

    // ── official plugin: universal delivery ───────────────────────────────────────
    Route::get('/delivery/providers', $h(PluginOperationsController::class, 'deliveryProviders'));
    Route::post('/delivery/quote', $h(PluginOperationsController::class, 'deliveryQuote'));
    Route::get('/delivery/shipments', $h(PluginOperationsController::class, 'deliveryList'));
    Route::post('/delivery/shipments', $h(PluginOperationsController::class, 'deliveryCreate'));
    Route::get('/delivery/shipments/{id}', $h(PluginOperationsController::class, 'deliveryGet'));
    Route::post('/delivery/shipments/{id}/refresh', $h(PluginOperationsController::class, 'deliveryRefresh'));
    Route::post('/delivery/shipments/{id}/cancel', $h(PluginOperationsController::class, 'deliveryCancel'));

    // ── official plugin: telephony ────────────────────────────────────────────────
    Route::get('/telephony/provider', $h(PluginOperationsController::class, 'telephonyProvider'));
    Route::get('/telephony/numbers', $h(PluginOperationsController::class, 'telephonyNumbers'));
    Route::post('/telephony/numbers/sync', $h(PluginOperationsController::class, 'telephonySyncNumbers'));
    Route::get('/telephony/calls', $h(PluginOperationsController::class, 'telephonyCalls'));
    Route::post('/telephony/calls', $h(PluginOperationsController::class, 'telephonyPlaceCall'));
    Route::get('/telephony/calls/{id}', $h(PluginOperationsController::class, 'telephonyCall'));
    Route::post('/telephony/calls/{id}/end', $h(PluginOperationsController::class, 'telephonyEndCall'));
    Route::get('/telephony/calls/{id}/recording', $h(PluginOperationsController::class, 'telephonyRecording'));

    // ── developer portal ─────────────────────────────────────────────────────────
    Route::get('/developer/profile', $h(DeveloperController::class, 'profile'));
    Route::post('/developer/profile', $h(DeveloperController::class, 'registerProfile'));
    Route::get('/developer/permissions', $h(DeveloperController::class, 'permissionCatalog'));
    Route::get('/developer/categories', $h(DeveloperController::class, 'categories'));
    Route::get('/developer/plugins', $h(DeveloperController::class, 'plugins'));
    Route::post('/developer/plugins', $h(DeveloperController::class, 'createPlugin'));
    Route::get('/developer/plugins/{id}', $h(DeveloperController::class, 'plugin'));
    Route::patch('/developer/plugins/{id}', $h(DeveloperController::class, 'updatePlugin'));
    Route::post('/developer/plugins/{id}/versions', $h(DeveloperController::class, 'createVersion'));
    Route::post('/developer/versions/{versionId}/submit', $h(DeveloperController::class, 'submitVersion'));

    // ── platform superadmin: marketplace ─────────────────────────────────────────
    Route::get('/platform/marketplace/overview', $h(AdminMarketplaceController::class, 'overview'));
    Route::get('/platform/marketplace/permissions', $h(AdminMarketplaceController::class, 'permissions'));
    Route::get('/platform/marketplace/reviews', $h(AdminMarketplaceController::class, 'reviews'));
    Route::get('/platform/marketplace/developers', $h(AdminMarketplaceController::class, 'developers'));
    Route::get('/platform/marketplace/categories', $h(AdminMarketplaceController::class, 'categories'));
    Route::get('/platform/marketplace/plan-access', $h(AdminMarketplaceController::class, 'planAccess'));
    Route::get('/platform/marketplace/feature-rules', $h(AdminMarketplaceController::class, 'featureRules'));
    Route::get('/platform/marketplace/limits', $h(AdminMarketplaceController::class, 'limits'));
    Route::get('/platform/marketplace/reports', $h(AdminMarketplaceController::class, 'reports'));
    Route::get('/platform/marketplace/audit', $h(AdminMarketplaceController::class, 'audit'));
    Route::get('/platform/marketplace/load-errors', $h(AdminMarketplaceController::class, 'loadErrors'));
    Route::get('/platform/marketplace/plugins', $h(AdminMarketplaceController::class, 'plugins'));
    Route::post('/platform/marketplace/plugins', $h(AdminMarketplaceController::class, 'createPlugin'));

    Route::post('/platform/marketplace/events/requeue', $h(AdminMarketplaceController::class, 'requeueEvents'));
    Route::post('/platform/marketplace/developers', $h(AdminMarketplaceController::class, 'createDeveloper'));
    Route::patch('/platform/marketplace/developers/{id}', $h(AdminMarketplaceController::class, 'updateDeveloper'));
    Route::post('/platform/marketplace/categories', $h(AdminMarketplaceController::class, 'createCategory'));
    Route::patch('/platform/marketplace/categories/{id}', $h(AdminMarketplaceController::class, 'updateCategory'));
    Route::delete('/platform/marketplace/categories/{id}', $h(AdminMarketplaceController::class, 'deleteCategory'));
    Route::put('/platform/marketplace/plan-access', $h(AdminMarketplaceController::class, 'updatePlanAccess'));
    Route::delete('/platform/marketplace/plan-access/{id}', $h(AdminMarketplaceController::class, 'deletePlanAccess'));
    Route::put('/platform/marketplace/feature-rules', $h(AdminMarketplaceController::class, 'updateFeatureRules'));
    Route::put('/platform/marketplace/limits', $h(AdminMarketplaceController::class, 'updateLimits'));
    Route::delete('/platform/marketplace/limits/{id}', $h(AdminMarketplaceController::class, 'deleteLimit'));

    // Version workflow: review, publish and retire are separate, explicit actions.
    Route::post('/platform/marketplace/versions/{id}/review', $h(AdminMarketplaceController::class, 'reviewVersion'));
    Route::post('/platform/marketplace/versions/{id}/publish', $h(AdminMarketplaceController::class, 'publishVersion'));
    Route::post('/platform/marketplace/versions/{id}/retire', $h(AdminMarketplaceController::class, 'retireVersion'));

    Route::post('/platform/marketplace/plugins/{id}/visibility', $h(AdminMarketplaceController::class, 'setVisibility'));
    Route::post('/platform/marketplace/plugins/{id}/block', $h(AdminMarketplaceController::class, 'blockPlugin'));
    Route::post('/platform/marketplace/plugins/{id}/deprecate', $h(AdminMarketplaceController::class, 'deprecatePlugin'));
    Route::post('/platform/marketplace/plugins/{id}/permission', $h(AdminMarketplaceController::class, 'grantPermission'));
    Route::get('/platform/marketplace/plugins/{id}', $h(AdminMarketplaceController::class, 'plugin'));
    Route::patch('/platform/marketplace/plugins/{id}', $h(AdminMarketplaceController::class, 'updatePlugin'));
});

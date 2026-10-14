<?php
use App\Http\Controllers\Api\V1\AiController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BillingController;
use App\Http\Controllers\Api\V1\CrmController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\ModulesController;
use App\Http\Controllers\Api\V1\OpsController;
use App\Http\Controllers\Api\V1\PlatformController;
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
    Route::get('/dashboard/workpanel', $h(DashboardController::class, 'workpanel'));
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
    Route::post('/documents', $h(ModulesController::class, 'createDocument'));
    Route::post('/documents/{id}/status', $h(ModulesController::class, 'setDocumentStatus'));
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
    Route::get('/ai/approvals', $h(AiController::class, 'approvals'));
    Route::post('/ai/approvals/{id}', $h(AiController::class, 'decideApproval'));
    Route::get('/ai/suppression', $h(AiController::class, 'suppression'));
    Route::post('/ai/suppression', $h(AiController::class, 'addSuppression'));
    Route::get('/ai/analytics', $h(AiController::class, 'analytics'));

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
});

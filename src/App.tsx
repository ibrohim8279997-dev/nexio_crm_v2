import { lazy, Suspense } from 'react'
import { Navigate, Route, Routes } from 'react-router-dom'
import { AppShell } from './components/layout/AppShell'
import { PlatformShell } from './components/layout/PlatformShell'
import { RequireAuth, RequirePlatform, RequirePortal } from './components/layout/Protected'
import { Card, Skeleton } from './components/ui/core'

const Landing = lazy(() => import('./pages/public/Landing'))
const Login = lazy(() => import('./pages/public/Login'))
const Register = lazy(() => import('./pages/public/Register'))
const ResetPassword = lazy(() => import('./pages/public/ResetPassword'))
const VerifyEmail = lazy(() => import('./pages/public/VerifyEmail'))
const InvitePage = lazy(() => import('./pages/public/InvitePage'))
const ChooseWorkspace = lazy(() => import('./pages/public/ChooseWorkspace'))
const Onboarding = lazy(() => import('./pages/public/Onboarding'))
const Marketing = lazy(() => import('./pages/public/Marketing'))
const Legal = lazy(() => import('./pages/public/Legal'))
const NotFound = lazy(() => import('./pages/public/NotFound'))

const Dashboard = lazy(() => import('./pages/app/Dashboard'))
const MyDay = lazy(() => import('./pages/app/MyDay'))
const Leads = lazy(() => import('./pages/app/Leads'))
const LeadDetail = lazy(() => import('./pages/app/LeadDetail'))
const ContactDetail = lazy(() => import('./pages/app/ContactDetail'))
const DealDetail = lazy(() => import('./pages/app/DealDetail'))
const OrderDetail = lazy(() => import('./pages/app/OrderDetail'))
const Deals = lazy(() => import('./pages/app/Deals'))
const Companies = lazy(() => import('./pages/app/Companies'))
const CompanyDetail = lazy(() => import('./pages/app/CompanyDetail'))
const Contacts = lazy(() => import('./pages/app/Contacts'))
const Orders = lazy(() => import('./pages/app/Orders'))
const CatalogPage = lazy(() => import('./pages/app/Catalog'))
const Inventory = lazy(() => import('./pages/app/Inventory'))
const InventoryProcurement = lazy(() => import('./pages/app/InventoryProcurement'))
const Projects = lazy(() => import('./pages/app/Projects'))
const ProjectDetail = lazy(() => import('./pages/app/ProjectDetail'))
const Tasks = lazy(() => import('./pages/app/Tasks'))
const CalendarPage = lazy(() => import('./pages/app/Calendar'))
const Communications = lazy(() => import('./pages/app/Communications'))
const Helpdesk = lazy(() => import('./pages/app/Helpdesk'))
const DocumentsPage = lazy(() => import('./pages/app/Documents'))
const Finance = lazy(() => import('./pages/app/Finance'))
const Analytics = lazy(() => import('./pages/app/Analytics'))
const Automations = lazy(() => import('./pages/app/Automations'))
const Integrations = lazy(() => import('./pages/app/Integrations'))
const AiAgents = lazy(() => import('./pages/app/AiAgents'))
const AiAgentDetail = lazy(() => import('./pages/app/AiAgentDetail'))
const AiCalls = lazy(() => import('./pages/app/AiCalls'))
const AiAnalytics = lazy(() => import('./pages/app/AiAnalytics'))
const Marketplace = lazy(() => import('./pages/app/Marketplace'))
const PluginManager = lazy(() => import('./pages/app/PluginManager'))
const PluginWorkspace = lazy(() => import('./pages/app/PluginWorkspace'))
const NexioServices = lazy(() => import('./pages/app/NexioServices'))
const Billing = lazy(() => import('./pages/app/Billing'))
const SettingsPage = lazy(() => import('./pages/app/Settings'))
const Team = lazy(() => import('./pages/app/Team'))

const Portal = lazy(() => import('./pages/portal/Portal'))

const PlatformOverview = lazy(() => import('./pages/platform/Overview'))
const PlatformTenants = lazy(() => import('./pages/platform/Tenants'))
const PlatformPlans = lazy(() => import('./pages/platform/Plans'))
const PlatformSubscriptions = lazy(() => import('./pages/platform/Subscriptions'))
const PlatformUsage = lazy(() => import('./pages/platform/Usage'))
const PlatformServiceOrders = lazy(() => import('./pages/platform/ServiceOrders'))
const PlatformSupport = lazy(() => import('./pages/platform/Support'))
const PlatformAiOps = lazy(() => import('./pages/platform/AiOps'))
const PlatformSecurity = lazy(() => import('./pages/platform/Security'))
const PlatformSettings = lazy(() => import('./pages/platform/PlatformSettings'))
const PlatformMarketplace = lazy(() => import('./pages/platform/Marketplace'))

function RouteSkeleton() {
  return (
    <div className="space-y-4">
      <Skeleton className="h-7 w-56" />
      <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        {Array.from({ length: 4 }).map((_, i) => <Card key={i} className="space-y-3 p-4"><Skeleton className="h-3 w-1/2" /><Skeleton className="h-6 w-24" /><Skeleton className="h-2 w-3/4" /></Card>)}
      </div>
      <Card className="space-y-3 p-5"><Skeleton className="h-4 w-1/4" /><Skeleton className="h-52 w-full" /></Card>
    </div>
  )
}

export function App() {
  return (
    <Suspense fallback={<RouteSkeleton />}>
      <Routes>
        {/* Public */}
        <Route path="/" element={<Landing />} />
        <Route path="/login" element={<Login />} />
        <Route path="/register" element={<Register />} />
        <Route path="/reset-password" element={<ResetPassword />} />
        <Route path="/reset-password/:token" element={<ResetPassword />} />
        <Route path="/verify-email/:token" element={<VerifyEmail />} />
        <Route path="/invite/:token" element={<InvitePage />} />
        <Route path="/choose-workspace" element={<RequireAuth><ChooseWorkspace /></RequireAuth>} />
        <Route path="/onboarding" element={<RequireAuth><Onboarding /></RequireAuth>} />
        <Route path="/pricing" element={<Marketing page="pricing" />} />
        <Route path="/features" element={<Marketing page="features" />} />
        <Route path="/security" element={<Marketing page="security" />} />
        <Route path="/legal/:slug" element={<Legal />} />

        {/* Client portal */}
        <Route path="/portal/*" element={<RequirePortal><Portal /></RequirePortal>} />

        {/* Platform superadmin */}
        <Route path="/platform/*" element={<RequirePlatform><PlatformShell><Routes>
          <Route index element={<PlatformOverview />} />
          <Route path="tenants" element={<PlatformTenants />} />
          <Route path="tenants/:id" element={<PlatformTenants />} />
          <Route path="plans" element={<PlatformPlans />} />
          <Route path="subscriptions" element={<PlatformSubscriptions />} />
          <Route path="usage" element={<PlatformUsage />} />
          <Route path="nexio-orders" element={<PlatformServiceOrders />} />
          <Route path="support" element={<PlatformSupport />} />
          <Route path="ai-ops" element={<PlatformAiOps />} />
          <Route path="security" element={<PlatformSecurity />} />
          <Route path="settings" element={<PlatformSettings />} />
          <Route path="marketplace" element={<PlatformMarketplace />} />
          <Route path="*" element={<Navigate to="/platform" replace />} />
        </Routes></PlatformShell></RequirePlatform>} />

        {/* Tenant workspace */}
        <Route path="/app/*" element={<RequireAuth><AppShell><Routes>
          <Route index element={<Dashboard />} />
          <Route path="my-day" element={<MyDay />} />
          <Route path="leads" element={<Leads />} />
          <Route path="leads/:id" element={<LeadDetail />} />
          <Route path="contacts" element={<Contacts />} />
          <Route path="contacts/:id" element={<ContactDetail />} />
          <Route path="companies" element={<Companies />} />
          <Route path="companies/:id" element={<CompanyDetail />} />
          <Route path="deals" element={<Deals />} />
          <Route path="deals/:id" element={<DealDetail />} />
          <Route path="pipelines" element={<Navigate to="/app/deals" replace />} />
          <Route path="orders" element={<Orders />} />
          <Route path="orders/:id" element={<OrderDetail />} />
          <Route path="catalog" element={<CatalogPage />} />
          <Route path="inventory" element={<Inventory />} />
          <Route path="inventory/procurement" element={<InventoryProcurement />} />
          <Route path="projects" element={<Projects />} />
          <Route path="projects/:id" element={<ProjectDetail />} />
          <Route path="tasks" element={<Tasks />} />
          <Route path="calendar" element={<CalendarPage />} />
          <Route path="team" element={<Team />} />
          <Route path="finance" element={<Finance />} />
          <Route path="communications" element={<Communications />} />
          <Route path="helpdesk" element={<Helpdesk />} />
          <Route path="documents" element={<DocumentsPage />} />
          <Route path="analytics" element={<Analytics />} />
          <Route path="automations" element={<Automations />} />
          <Route path="integrations" element={<Integrations />} />
          <Route path="marketplace" element={<Marketplace />} />
          <Route path="marketplace/:id" element={<Marketplace />} />
          <Route path="plugins" element={<PluginManager />} />
          <Route path="plugins/:id" element={<PluginWorkspace />} />
          <Route path="plugins/:id/workspace" element={<PluginWorkspace />} />
          <Route path="ai-agents" element={<AiAgents />} />
          <Route path="ai-agents/:id" element={<AiAgentDetail />} />
          <Route path="ai-calls" element={<AiCalls />} />
          <Route path="ai-analytics" element={<AiAnalytics />} />
          <Route path="nexio-services" element={<NexioServices />} />
          <Route path="billing" element={<Billing />} />
          <Route path="settings" element={<SettingsPage />} />
          <Route path="*" element={<NotFound />} />
        </Routes></AppShell></RequireAuth>} />

        <Route path="*" element={<NotFound />} />
      </Routes>
    </Suspense>
  )
}

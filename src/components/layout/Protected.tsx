import { Navigate, useLocation } from 'react-router-dom'
import type { ReactNode } from 'react'
import { useAuth } from '../../lib/auth'
import { Card, Skeleton } from '../ui/core'

function Booting() {
  return (
    <div className="mx-auto max-w-5xl space-y-4 p-4">
      <Skeleton className="h-8 w-64" />
      <Card className="space-y-3 p-5">
        <Skeleton className="h-4 w-1/3" />
        <Skeleton className="h-3 w-full" />
        <Skeleton className="h-3 w-4/5" />
        <Skeleton className="h-40 w-full" />
      </Card>
    </div>
  )
}

/** Requires an authenticated session; otherwise returns to login preserving the target. */
export function RequireAuth({ children }: { children: ReactNode }) {
  const { user, loading } = useAuth()
  const location = useLocation()
  if (loading) return <Booting />
  if (!user) return <Navigate to="/login" replace state={{ from: location.pathname }} />
  return <>{children}</>
}

/** Requires a platform operator role — tenant users get an explicit 403 screen. */
export function RequirePlatform({ children }: { children: ReactNode }) {
  const { user, isPlatformAdmin, loading } = useAuth()
  if (loading) return <Booting />
  if (!user) return <Navigate to="/login" replace state={{ from: '/platform' }} />
  if (!isPlatformAdmin) {
    return (
      <div className="mx-auto max-w-lg p-8 text-center">
        <h1 className="text-xl font-bold text-ink">403 · Доступ запрещён</h1>
        <p className="mt-2 text-[13px] text-muted">
          Раздел доступен только операторам платформы Nexio Lab. Ваша сессия не имеет прав <code>platform_superadmin</code>.
        </p>
      </div>
    )
  }
  return <>{children}</>
}

import { useEffect, useRef } from 'react'
import { useLocation } from 'react-router-dom'
import { api } from '../../lib/api'
import { isNative } from '../../lib/native'

/** First-party page-view beacon (see the Privacy and Cookie policies). The server drops bots, platform admins and DNT/GPC visitors. */
export function PageTracker() {
  const { pathname } = useLocation()
  const last = useRef<string | null>(null)
  useEffect(() => {
    if (pathname === last.current || pathname.startsWith('/platform')) return
    const referrer = last.current === null ? document.referrer : ''
    last.current = pathname
    api.post('/api/v1/site/track', { path: pathname, referrer, native: isNative() }).catch(() => { /* analytics must never affect the page */ })
  }, [pathname])
  return null
}

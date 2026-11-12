import { useEffect, useRef, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { CheckCircle2, XCircle } from 'lucide-react'
import { Card } from '../../components/ui/core'
import { api, ApiError } from '../../lib/api'
import { BrandMark } from './Login'

export default function VerifyEmail() {
  const { token } = useParams()
  const [state, setState] = useState<'pending' | 'done' | 'failed'>('pending')
  const [message, setMessage] = useState('')
  // The token is single-use and StrictMode runs effects twice in development.
  const sent = useRef(false)

  useEffect(() => {
    if (sent.current) return
    sent.current = true
    api.post('/api/v1/auth/verify-email', { token })
      .then(() => setState('done'))
      .catch((err) => { setMessage(err instanceof ApiError ? err.message : 'Tasdiqlab bo‘lmadi'); setState('failed') })
  }, [token])

  return (
    <div className="flex min-h-dvh items-center justify-center bg-gradient-to-b from-blue-50 via-bg to-bg px-4 py-10">
      <div className="w-full max-w-md">
        <div className="flex justify-center"><BrandMark /></div>
        <Card className="mt-6 p-6 text-center">
          {state === 'pending' && <p className="text-[13px] text-muted">Tekshirilmoqda…</p>}
          {state === 'done' && <><CheckCircle2 className="mx-auto size-10 text-success" /><h1 className="mt-3 text-lg font-bold text-ink">Email tasdiqlandi</h1></>}
          {state === 'failed' && <><XCircle className="mx-auto size-10 text-danger" /><h1 className="mt-3 text-lg font-bold text-ink">{message}</h1></>}
          {state !== 'pending' && <Link to="/app" className="mt-4 inline-block text-[13px] font-semibold text-primary hover:underline">CRM’ga o‘tish</Link>}
        </Card>
      </div>
    </div>
  )
}

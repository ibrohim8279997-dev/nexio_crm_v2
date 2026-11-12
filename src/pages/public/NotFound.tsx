import { Link } from 'react-router-dom'
import { Button, Card } from '../../components/ui/core'
import { useAuth } from '../../lib/auth'

export default function NotFound() {
  const { user } = useAuth()
  return (
    <div className="flex min-h-dvh items-center justify-center bg-bg px-4">
      <Card className="w-full max-w-md p-8 text-center">
        <p className="text-[11px] font-semibold uppercase tracking-widest text-primary">404</p>
        <h1 className="mt-2 text-xl font-bold text-ink">Страница не найдена</h1>
        <p className="mt-2 text-[13px] leading-relaxed text-muted">
          Запрошенный раздел не существует или был перемещён. Проверьте адрес либо вернитесь в рабочее пространство.
        </p>
        <div className="mt-5 flex justify-center gap-2">
          <Link to={user ? '/app' : '/login'}><Button size="sm">{user ? 'В рабочее пространство' : 'Войти'}</Button></Link>
          <Link to="/"><Button variant="secondary" size="sm">На главную</Button></Link>
        </div>
      </Card>
    </div>
  )
}

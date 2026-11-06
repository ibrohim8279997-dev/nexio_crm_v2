import { useQueryClient } from '@tanstack/react-query'
import { Button, Card } from './core'
import { useApi, useApiMutation } from '../../lib/hooks'
import { api } from '../../lib/api'

/** Plugin screens are unusable until the plugin is enabled, so they offer that step in place. */
export function PluginEnableNotice({ pluginId }: { pluginId: string }) {
  const queryClient = useQueryClient()
  const installed = useApi<{ data: { plugin_id: string; status: string }[]; can_manage: boolean }>(['plugins', 'installed'], '/api/v1/plugins/installed')
  const enable = useApiMutation(() => api.post(`/api/v1/plugins/${pluginId}/enable`), {
    invalidate: [['plugins'], ['plugin', pluginId]], success: 'Plugin yoqildi',
    onDone: () => { void queryClient.invalidateQueries() },
  })
  const status = installed.data?.data.find((plugin) => plugin.plugin_id === pluginId)?.status
  if (status !== 'installed' && status !== 'disabled') return null
  return (
    <Card className="flex flex-wrap items-center justify-between gap-2 border-amber-200 bg-amber-50 px-4 py-3">
      <p className="text-[13px] text-amber-900">Plugin o‘rnatilgan, lekin yoqilmagan. Ishlatish uchun uni yoqing.</p>
      {installed.data?.can_manage
        ? <Button size="sm" loading={enable.isPending} onClick={() => enable.mutate(undefined)}>Yoqish</Button>
        : <p className="text-[11px] text-amber-900">Administrator yoqishi kerak.</p>}
    </Card>
  )
}

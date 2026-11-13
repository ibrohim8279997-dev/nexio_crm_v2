import { useEffect, useState } from 'react'
import { ChevronLeft, ChevronRight } from 'lucide-react'
import { resolveUrl } from '../../lib/api'

export interface SiteBanner { id: string; image_url: string; link_url: string | null; alt?: string | null }

export const BANNER_INTERVAL_MS = 4000
const safeLink = (u: string | null) => (u && (/^https?:\/\//i.test(u) || /^\/(?!\/)/.test(u)) ? u : null)

/**
 * Landing-page banner slider. One banner is static; several rotate every 4 seconds (paused on hover/focus and with
 * "reduce motion"). A banner is a link only when the superadmin set a URL, otherwise it is a plain, non-clickable image.
 * The admin preview renders this same component, so what the superadmin sees is what visitors get.
 */
export function BannerCarousel({ banners }: { banners: SiteBanner[] }) {
  const [index, setIndex] = useState(0)
  const [paused, setPaused] = useState(false)
  const count = banners.length
  const reduce = typeof window !== 'undefined' && window.matchMedia?.('(prefers-reduced-motion: reduce)').matches

  useEffect(() => { if (index >= count) setIndex(0) }, [count, index])
  useEffect(() => {
    if (count < 2 || paused || reduce) return
    const t = window.setInterval(() => setIndex((i) => (i + 1) % count), BANNER_INTERVAL_MS)
    return () => window.clearInterval(t)
  }, [count, paused, reduce, index])

  if (count === 0) return null
  const go = (d: number) => setIndex((i) => (i + d + count) % count)

  return (
    <section aria-roledescription="carousel" aria-label="Yangiliklar va takliflar" className="mx-auto max-w-6xl px-4 pt-4"
      onMouseEnter={() => setPaused(true)} onMouseLeave={() => setPaused(false)} onFocus={() => setPaused(true)} onBlur={() => setPaused(false)}>
      <div className="relative overflow-hidden rounded-2xl border border-line bg-subtle">
        <div className="flex transition-transform duration-500 ease-out motion-reduce:transition-none" style={{ transform: `translateX(-${index * 100}%)` }}>
          {banners.map((b, i) => {
            const link = safeLink(b.link_url)
            const external = !!link && /^https?:\/\//i.test(link)
            const img = <img src={resolveUrl(b.image_url)} alt={b.alt ?? ''} className="block h-auto max-h-[320px] w-full object-cover" draggable={false} loading={i === 0 ? 'eager' : 'lazy'} />
            return (
              <div key={b.id} className="w-full shrink-0" role="group" aria-roledescription="slide" aria-label={`${i + 1} / ${count}`} aria-hidden={i !== index}>
                {link
                  ? <a href={link} tabIndex={i === index ? 0 : -1} {...(external ? { target: '_blank', rel: 'noopener noreferrer' } : {})} className="block cursor-pointer">{img}</a>
                  : img}
              </div>
            )
          })}
        </div>
        {count > 1 && <>
          <button type="button" onClick={() => go(-1)} aria-label="Oldingi banner" className="absolute left-2 top-1/2 hidden size-9 -translate-y-1/2 items-center justify-center rounded-full bg-black/40 text-white hover:bg-black/60 sm:inline-flex"><ChevronLeft className="size-5" /></button>
          <button type="button" onClick={() => go(1)} aria-label="Keyingi banner" className="absolute right-2 top-1/2 hidden size-9 -translate-y-1/2 items-center justify-center rounded-full bg-black/40 text-white hover:bg-black/60 sm:inline-flex"><ChevronRight className="size-5" /></button>
          <div className="absolute inset-x-0 bottom-2 flex justify-center gap-1.5">
            {banners.map((b, i) => <button key={b.id} type="button" onClick={() => setIndex(i)} aria-label={`${i + 1}-banner`} aria-current={i === index}
              className={`h-2 rounded-full transition-all ${i === index ? 'w-5 bg-white' : 'w-2 bg-white/60'}`} />)}
          </div>
        </>}
      </div>
    </section>
  )
}

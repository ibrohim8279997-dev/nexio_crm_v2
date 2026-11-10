import markUrl from '../../assets/nexio-lab-mark.png'
import fullUrl from '../../assets/nexio-lab-logo-full.png'

/** Official Nexio Lab logo mark. The white plate keeps the dark-navy half readable in the dark theme
 *  (bg-[#fff] is used on purpose: the theme overrides bg-white). */
export function NexioMark({ className = 'size-10' }: { className?: string }) {
  return <img src={markUrl} alt="Nexio Lab" className={`${className} shrink-0 rounded-xl bg-[#fff] object-contain p-1`} />
}

/** Full logo (mark + wordmark + tagline) on its own white plate, for hero / auth surfaces. */
export function NexioLogoFull({ className = 'h-24' }: { className?: string }) {
  return <img src={fullUrl} alt="Nexio Lab — Future starts here" className={`${className} w-auto rounded-2xl bg-[#fff] object-contain`} />
}

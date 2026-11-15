/**
 * Turns a Markdown document into a downloadable file: DOCX (default), PDF, XLSX, Markdown or plain text.
 * Everything runs in the browser; the heavy libraries are loaded only when that format is chosen.
 */
export type ExportFormat = 'docx' | 'pdf' | 'xlsx' | 'md' | 'txt'
export const EXPORT_FORMATS: ExportFormat[] = ['docx', 'pdf', 'xlsx', 'md', 'txt']
export const DEFAULT_EXPORT_FORMAT: ExportFormat = 'docx'

export type Block =
  | { t: 'h'; level: 1 | 2 | 3; text: string }
  | { t: 'p'; text: string }
  | { t: 'ul'; items: string[] }
  | { t: 'ol'; items: string[] }
  | { t: 'table'; rows: string[][] }
  | { t: 'hr' }
export interface Run { text: string; bold?: boolean; italics?: boolean }

const cells = (line: string) => line.trim().replace(/^\|/, '').replace(/\|$/, '').split('|').map((c) => c.trim())
const isSeparator = (line: string) => /^\s*\|?\s*:?-{2,}:?\s*(\|\s*:?-{2,}:?\s*)*\|?\s*$/.test(line) && line.includes('-')

/** A small Markdown subset: headings, paragraphs, bullet/numbered lists, pipe tables and horizontal rules. */
export function parseMarkdown(md: string): Block[] {
  const lines = md.replace(/\r\n?/g, '\n').split('\n')
  const out: Block[] = []
  let para: string[] = []
  const flush = () => { if (para.length) { out.push({ t: 'p', text: para.join(' ') }); para = [] } }
  for (let i = 0; i < lines.length; i++) {
    const line = lines[i]
    const trimmed = line.trim()
    let m: RegExpMatchArray | null
    if (!trimmed) { flush(); continue }
    if ((m = trimmed.match(/^(#{1,6})\s+(.*)$/))) { flush(); out.push({ t: 'h', level: Math.min(m[1].length, 3) as 1 | 2 | 3, text: m[2].replace(/\s*#+\s*$/, '') }); continue }
    if (/^([-*_])(\s*\1){2,}$/.test(trimmed)) { flush(); out.push({ t: 'hr' }); continue }
    if (trimmed.startsWith('|') && i + 1 < lines.length && isSeparator(lines[i + 1])) {
      flush()
      const rows = [cells(trimmed)]
      i += 2
      while (i < lines.length && lines[i].trim().startsWith('|')) rows.push(cells(lines[i++]))
      i--
      out.push({ t: 'table', rows })
      continue
    }
    if ((m = trimmed.match(/^[-*+]\s+(.*)$/))) {
      flush()
      const last = out[out.length - 1]
      if (last?.t === 'ul') last.items.push(m[1]); else out.push({ t: 'ul', items: [m[1]] })
      continue
    }
    if ((m = trimmed.match(/^\d+[.)]\s+(.*)$/))) {
      flush()
      const last = out[out.length - 1]
      if (last?.t === 'ol') last.items.push(m[1]); else out.push({ t: 'ol', items: [m[1]] })
      continue
    }
    para.push(trimmed)
  }
  flush()
  return out
}

/** Splits inline Markdown (**bold**, *italic*, `code`, [text](url)) into styled runs. */
export function inlineRuns(text: string): Run[] {
  const runs: Run[] = []
  const re = /\*\*([^*]+)\*\*|__([^_]+)__|\*([^*\s][^*]*)\*|`([^`]+)`|\[([^\]]+)\]\(([^)]+)\)/g
  let last = 0
  let m: RegExpExecArray | null
  while ((m = re.exec(text))) {
    if (m.index > last) runs.push({ text: text.slice(last, m.index) })
    if (m[1] ?? m[2]) runs.push({ text: m[1] ?? m[2], bold: true })
    else if (m[3]) runs.push({ text: m[3], italics: true })
    else if (m[4]) runs.push({ text: m[4] })
    else runs.push({ text: `${m[5]} (${m[6]})` })
    last = m.index + m[0].length
  }
  if (last < text.length) runs.push({ text: text.slice(last) })
  return runs.length ? runs : [{ text }]
}
export const plain = (text: string) => inlineRuns(text).map((r) => r.text).join('')

export const safeFileName = (name: string, ext: string) =>
  `${(name || 'document').replace(/[\\/:*?"<>|\x00-\x1f]+/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 80) || 'document'}.${ext}`

// ── DOCX ────────────────────────────────────────────────────────────────────
async function toDocx(title: string, blocks: Block[]): Promise<Blob> {
  const d = await import('docx')
  const run = (r: Run) => new d.TextRun({ text: r.text, bold: r.bold, italics: r.italics })
  const runs = (text: string) => inlineRuns(text).map(run)
  const heading = [d.HeadingLevel.HEADING_1, d.HeadingLevel.HEADING_2, d.HeadingLevel.HEADING_3]
  const children: (InstanceType<typeof d.Paragraph> | InstanceType<typeof d.Table>)[] = []
  for (const b of blocks) {
    if (b.t === 'h') children.push(new d.Paragraph({ heading: heading[b.level - 1], children: runs(b.text), spacing: { before: 240, after: 120 } }))
    else if (b.t === 'p') children.push(new d.Paragraph({ children: runs(b.text), spacing: { after: 120 } }))
    else if (b.t === 'ul') b.items.forEach((it) => children.push(new d.Paragraph({ children: runs(it), bullet: { level: 0 } })))
    else if (b.t === 'ol') b.items.forEach((it, i) => children.push(new d.Paragraph({ children: [new d.TextRun(`${i + 1}. `), ...runs(it)], indent: { left: 360 } })))
    else if (b.t === 'hr') children.push(new d.Paragraph({ border: { bottom: { style: d.BorderStyle.SINGLE, size: 6, color: '999999', space: 1 } } }))
    else if (b.t === 'table') {
      const width = Math.max(...b.rows.map((r) => r.length))
      children.push(new d.Table({
        width: { size: 100, type: d.WidthType.PERCENTAGE },
        rows: b.rows.map((row, ri) => new d.TableRow({
          tableHeader: ri === 0,
          children: Array.from({ length: width }, (_, ci) => new d.TableCell({
            children: [new d.Paragraph({ children: inlineRuns(row[ci] ?? '').map((r) => new d.TextRun({ text: r.text, bold: r.bold || ri === 0, italics: r.italics })) })],
          })),
        })),
      }))
      children.push(new d.Paragraph({ text: '' }))
    }
  }
  const doc = new d.Document({ title, creator: 'Nexio CRM', sections: [{ children }] })
  return d.Packer.toBlob(doc)
}

// ── PDF ─────────────────────────────────────────────────────────────────────
// The bundled Roboto font covers Latin, Latin Extended and Cyrillic; Uzbek's modifier apostrophes are not in it.
const pdfSafe = (s: string) => s.replace(/[ʻʼ‘’ʹ]/g, "'")

async function toPdf(title: string, blocks: Block[]): Promise<Blob> {
  const pdfMake = ((await import('pdfmake/build/pdfmake')) as any).default ?? (await import('pdfmake/build/pdfmake'))
  const vfsModule = (await import('pdfmake/build/vfs_fonts')) as any
  pdfMake.addVirtualFileSystem?.(vfsModule.default ?? vfsModule)
  const runs = (text: string) => inlineRuns(text).map((r) => ({ text: pdfSafe(r.text), bold: r.bold, italics: r.italics }))
  const size = [20, 16, 13]
  const content: any[] = []
  for (const b of blocks) {
    if (b.t === 'h') content.push({ text: runs(b.text), fontSize: size[b.level - 1], bold: true, margin: [0, 10, 0, 4] })
    else if (b.t === 'p') content.push({ text: runs(b.text), margin: [0, 0, 0, 6] })
    else if (b.t === 'ul') content.push({ ul: b.items.map((i) => ({ text: runs(i) })), margin: [0, 0, 0, 6] })
    else if (b.t === 'ol') content.push({ ol: b.items.map((i) => ({ text: runs(i) })), margin: [0, 0, 0, 6] })
    else if (b.t === 'hr') content.push({ canvas: [{ type: 'line', x1: 0, y1: 0, x2: 515, y2: 0, lineWidth: 0.7, lineColor: '#999999' }], margin: [0, 4, 0, 8] })
    else if (b.t === 'table') {
      const width = Math.max(...b.rows.map((r) => r.length))
      content.push({
        table: { headerRows: 1, widths: Array(width).fill('*'), body: b.rows.map((row, ri) => Array.from({ length: width }, (_, ci) => ({ text: runs(row[ci] ?? ''), bold: ri === 0 }))) },
        margin: [0, 0, 0, 8],
      })
    }
  }
  const def = { info: { title: pdfSafe(title), creator: 'Nexio CRM' }, pageSize: 'A4', pageMargins: [40, 50, 40, 50], content, defaultStyle: { font: 'Roboto', fontSize: 11 } }
  return new Promise<Blob>((resolve, reject) => {
    try { pdfMake.createPdf(def).getBlob((blob: Blob) => resolve(blob)) } catch (e) { reject(e) }
  })
}

// ── XLSX ────────────────────────────────────────────────────────────────────
async function toXlsx(blocks: Block[]): Promise<Blob> {
  const writeXlsxFile = (await import('write-excel-file')).default as (data: any, opts?: any) => Promise<Blob>
  const rows: any[][] = []
  const wrap = { wrap: true, alignVertical: 'top' as const }
  for (const b of blocks) {
    if (b.t === 'h') rows.push([{ value: plain(b.text), fontWeight: 'bold', fontSize: b.level === 1 ? 16 : 13, ...wrap }])
    else if (b.t === 'p') rows.push([{ value: plain(b.text), ...wrap }])
    else if (b.t === 'ul') b.items.forEach((i) => rows.push([{ value: `• ${plain(i)}`, ...wrap }]))
    else if (b.t === 'ol') b.items.forEach((i, n) => rows.push([{ value: `${n + 1}. ${plain(i)}`, ...wrap }]))
    else if (b.t === 'table') {
      b.rows.forEach((r, ri) => rows.push(r.map((c) => {
        const text = plain(c)
        const num = text !== '' && /^-?\d+([.,]\d+)?$/.test(text.replace(/\s/g, '')) ? Number(text.replace(/\s/g, '').replace(',', '.')) : null
        return num !== null && ri > 0 ? { value: num, type: Number } : { value: text, fontWeight: ri === 0 ? 'bold' : undefined, ...wrap }
      })))
    }
    if (b.t !== 'hr') rows.push([])
    else rows.push([])
  }
  if (!rows.length) rows.push([{ value: '' }])
  const cols = Math.max(...rows.map((r) => r.length), 1)
  return writeXlsxFile(rows, { columns: Array.from({ length: cols }, (_, i) => ({ width: i === 0 ? 70 : 28 })) })
}

export async function exportDocument(title: string, markdown: string, format: ExportFormat): Promise<{ blob: Blob; filename: string }> {
  const blocks = parseMarkdown(markdown)
  switch (format) {
    case 'docx': return { blob: await toDocx(title, blocks), filename: safeFileName(title, 'docx') }
    case 'pdf': return { blob: await toPdf(title, blocks), filename: safeFileName(title, 'pdf') }
    case 'xlsx': return { blob: await toXlsx(blocks), filename: safeFileName(title, 'xlsx') }
    case 'md': return { blob: new Blob([markdown], { type: 'text/markdown;charset=utf-8' }), filename: safeFileName(title, 'md') }
    default: {
      const text = blocks.map((b) => b.t === 'h' || b.t === 'p' ? plain(b.text) : b.t === 'ul' ? b.items.map((i) => `- ${plain(i)}`).join('\n') : b.t === 'ol' ? b.items.map((i, n) => `${n + 1}. ${plain(i)}`).join('\n')
        : b.t === 'table' ? b.rows.map((r) => r.map(plain).join('\t')).join('\n') : '----').join('\n\n')
      return { blob: new Blob([text], { type: 'text/plain;charset=utf-8' }), filename: safeFileName(title, 'txt') }
    }
  }
}

// ── filling in AI gaps ──────────────────────────────────────────────────────
const GAP = /\[MISSING:\s*([^\]]{1,200})\]/g
/** Unique gap descriptions in the order they appear (mirrors App\Support\AiDocument::missing). */
export function findGaps(content: string): string[] {
  return [...new Set([...content.matchAll(GAP)].map((m) => m[1].trim()))]
}
/** Replaces every occurrence of each described gap with the text the user entered; empty answers leave the gap in place. */
export function fillGaps(content: string, answers: Record<string, string>): string {
  return content.replace(GAP, (whole, desc: string) => (answers[desc.trim()]?.trim() ? answers[desc.trim()].trim() : whole))
}

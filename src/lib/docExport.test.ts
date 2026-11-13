// @vitest-environment jsdom
import { describe, expect, it } from 'vitest'
import JSZip from 'jszip'
import { exportDocument, fillGaps, findGaps, inlineRuns, parseMarkdown } from './docExport'

const MD = `# Taklif №1

Mijoz: **Acme** — *ommaviy*.

## Narxlar
| Nomi | Soni | Narx |
|---|---|---|
| CRM | 5 | [MISSING: narx] |

- Birinchi
- Ikkinchi

1. Bir
2. Ikki

---
Oxiri \`kod\` [sayt](https://x.uz)`

// jsdom's Blob has no arrayBuffer()/text(): read it through FileReader.
const bytes = (b: Blob) => new Promise<Uint8Array>((resolve, reject) => {
  const r = new FileReader()
  r.onload = () => resolve(new Uint8Array(r.result as ArrayBuffer)); r.onerror = () => reject(r.error)
  r.readAsArrayBuffer(b)
})
const text = async (b: Blob) => new TextDecoder().decode(await bytes(b))

describe('markdown parsing', () => {
  it('splits into blocks', () => {
    const b = parseMarkdown(MD)
    expect(b.map((x) => x.t)).toEqual(['h', 'p', 'h', 'table', 'ul', 'ol', 'hr', 'p'])
    expect((b[3] as { rows: string[][] }).rows).toEqual([['Nomi', 'Soni', 'Narx'], ['CRM', '5', '[MISSING: narx]']])
  })
  it('styles inline runs', () => {
    expect(inlineRuns('a **b** *c*')).toEqual([{ text: 'a ' }, { text: 'b', bold: true }, { text: ' ' }, { text: 'c', italics: true }])
  })
})

describe('gaps', () => {
  it('finds and fills each described gap everywhere', () => {
    const text = 'Narx: [MISSING: narx], muddat: [MISSING: yetkazish muddati], yana [MISSING: narx]'
    expect(findGaps(text)).toEqual(['narx', 'yetkazish muddati'])
    expect(fillGaps(text, { narx: '100 USD', 'yetkazish muddati': '  ' })).toBe('Narx: 100 USD, muddat: [MISSING: yetkazish muddati], yana 100 USD')
  })
})

describe('export', () => {
  it('writes a DOCX (a zip with the text inside)', async () => {
    const { blob, filename } = await exportDocument('Taklif / №1', MD, 'docx')
    expect(filename).toBe('Taklif №1.docx')
    const zip = await JSZip.loadAsync(await bytes(blob))
    const xml = await zip.file('word/document.xml')!.async('string')
    expect(xml).toContain('Taklif №1')
    expect(xml).toContain('[MISSING: narx]')
  })
  it('writes an XLSX', async () => {
    const { blob, filename } = await exportDocument('x', MD, 'xlsx')
    expect(filename).toBe('x.xlsx')
    const zip = await JSZip.loadAsync(await bytes(blob))
    expect(Object.keys(zip.files)).toContain('xl/worksheets/sheet1.xml')
  })
  it('writes a PDF with Cyrillic and Uzbek text', async () => {
    const { blob, filename } = await exportDocument('Тест', '# Привет\n\nO‘zbekcha matn: g‘isht, мир.', 'pdf')
    expect(filename).toBe('Тест.pdf')
    const head = new TextDecoder().decode((await bytes(blob)).slice(0, 5))
    expect(head).toBe('%PDF-')
  })
  it('writes md and txt', async () => {
    expect(await text((await exportDocument('a', '# T\n\n**b**', 'md')).blob)).toBe('# T\n\n**b**')
    expect(await text((await exportDocument('a', '# T\n\n**b**', 'txt')).blob)).toBe('T\n\nb')
  })
})

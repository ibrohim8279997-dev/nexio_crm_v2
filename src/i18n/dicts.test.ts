import { describe, expect, it } from 'vitest'
import { aiAccessDict } from './aiAccess'
import { documentsDict } from './documents'
import { siteAdminDict } from './siteAdmin'
import { telephonyDict } from './telephony'

const ALL = { documents: documentsDict, aiAccess: aiAccessDict, telephony: telephonyDict, siteAdmin: siteAdminDict }
const vars = (s: string) => [...s.matchAll(/\{(\w+)\}/g)].map((m) => m[1]).sort().join(',')

describe.each(Object.entries(ALL))('%s translations', (_name, dict) => {
  it('has the same keys in uz, ru and en', () => {
    const keys = Object.keys(dict.en).sort()
    expect(Object.keys(dict.uz).sort()).toEqual(keys)
    expect(Object.keys(dict.ru).sort()).toEqual(keys)
  })
  it('has no empty text and the same {placeholders} in every language', () => {
    for (const [key, en] of Object.entries(dict.en)) {
      for (const l of ['uz', 'ru'] as const) {
        expect(dict[l][key].trim(), `${l}.${key}`).not.toBe('')
        expect(vars(dict[l][key]), `${l}.${key}`).toBe(vars(en))
      }
    }
  })
})

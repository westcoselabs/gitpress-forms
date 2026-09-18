import builtinCatalog from '../../schema/fields.json' with { type: 'json' };
import type { Field, FormDefinition } from './types';
export type CatalogField = { type: string; label: string; group: string; container: boolean; settingsSchema?: Record<string, { type: string; title?: string; default?: unknown; enum?: string[] }> };
export const catalog: CatalogField[] = typeof window !== 'undefined' && window.GitPressFormsAdmin?.fieldTypes || builtinCatalog;
export const newId = () => crypto.randomUUID().replaceAll('-', '').slice(0, 12);
export function makeField(type: string): Field {
  const item = catalog.find(f => f.type === type);
  return { id: newId(), name: `${type}_${newId().slice(0, 6)}`, type, label: item?.label || type, required: false, placeholder: '', help: '', default: '', width: 12, options: ['First Choice', 'Second Choice', 'Third Choice'].map((label, i) => ({ label, value: `option_${i + 1}` })), children: [], ...(type === 'calculation' ? { formula: '0' } : {}), ...(type === 'grid' ? { rows: ['First row', 'Second row'] } : {}) };
}
export function emptyDefinition(): FormDefinition {
  return { schemaVersion: 1, fields: [], settings: { submitLabel: 'Submit Form', confirmation: 'Thank you! Your submission has been received.', redirect: '', requireLogin: false, maxEntries: 0, opensAt: '', closesAt: '', approval: false, doubleOptin: false, saveResume: false, mode: 'standard', retentionDays: 0, notifications: [], confirmations: [], css: '', js: '' }, style: { primary: '#3865e9', text: '#263348', background: '#ffffff', border: '#dce1eb', radius: 6, gap: 20, fontSize: 15, maxWidth: 780, labelPosition: 'top' } };
}
export function flatten(fields: Field[]): Field[] { return fields.flatMap(field => [field, ...flatten(field.children)]); }
export function updateField(fields: Field[], id: string, change: (field: Field) => Field): Field[] { return fields.map(f => f.id === id ? change(f) : { ...f, children: updateField(f.children, id, change) }); }
export function removeField(fields: Field[], id: string): Field[] { return fields.filter(f => f.id !== id).map(f => ({ ...f, children: removeField(f.children, id) })); }
export function cloneField(field: Field): Field { return { ...structuredClone(field), id: newId(), name: `${field.name}_${newId().slice(0, 4)}`, children: field.children.map(cloneField) }; }

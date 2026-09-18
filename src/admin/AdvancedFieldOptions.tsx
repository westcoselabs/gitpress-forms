import type { Field } from '../shared/types';
import { catalog } from '../shared/definition';
import { Setting } from './ui';

export function AdvancedFieldOptions({ field, onChange }: { field: Field; onChange: (patch: Partial<Field>) => void }) {
  return <>
    {Object.entries(catalog.find(item => item.type === field.type)?.settingsSchema || {}).map(([key, schema]) => <Setting key={key} label={schema.title || key}>{schema.enum ? <select value={String(field.extension?.[key] ?? schema.default ?? '')} onChange={e => onChange({ extension: { ...field.extension, [key]: e.target.value } })}>{schema.enum.map(value => <option key={value}>{value}</option>)}</select> : schema.type === 'boolean' ? <input type="checkbox" checked={Boolean(field.extension?.[key] ?? schema.default)} onChange={e => onChange({ extension: { ...field.extension, [key]: e.target.checked } })} /> : <input type={schema.type === 'number' || schema.type === 'integer' ? 'number' : 'text'} value={String(field.extension?.[key] ?? schema.default ?? '')} onChange={e => onChange({ extension: { ...field.extension, [key]: schema.type === 'number' || schema.type === 'integer' ? Number(e.target.value) : e.target.value } })} />}</Setting>)}
    {field.type === 'mask' ? <Setting label="Input Mask" help="9 = digit, a = letter, * = letter or digit. Other characters are fixed separators."><input value={field.mask || '999-999-9999'} onChange={e => onChange({ mask: e.target.value })} /></Setting> : null}
    {field.type === 'date' ? <Setting label="Date / Time Format"><select value={field.dateMode || 'date'} onChange={e => onChange({ dateMode: e.target.value as Field['dateMode'] })}><option value="date">Date</option><option value="time">Time</option><option value="datetime-local">Date and time</option></select></Setting> : null}
    {['text', 'textarea', 'richtext', 'password'].includes(field.type) ? <div className="two-columns"><Setting label="Minimum length"><input type="number" min={0} value={field.minLength ?? ''} onChange={e => onChange({ minLength: e.target.value ? Number(e.target.value) : undefined })} /></Setting><Setting label="Maximum length"><input type="number" min={0} value={field.maxLength ?? ''} onChange={e => onChange({ maxLength: e.target.value ? Number(e.target.value) : undefined })} /></Setting></div> : null}
  </>;
}

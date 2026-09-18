import type { ReactNode } from 'react';
import * as Dialog from '@radix-ui/react-dialog';
import { X, LoaderCircle, Inbox, ChevronDown } from 'lucide-react';
export function Modal({ title, open, onOpenChange, children, wide = false }: { title: string; open: boolean; onOpenChange: (open: boolean) => void; children: ReactNode; wide?: boolean }) {
  return <Dialog.Root open={open} onOpenChange={onOpenChange}><Dialog.Portal><Dialog.Overlay className="gpf-dialog-overlay" /><Dialog.Content className={`gpf-admin gpf-dialog ${wide ? 'gpf-dialog-wide' : ''}`} aria-describedby={undefined}><div className="dialog-heading"><Dialog.Title>{title}</Dialog.Title><Dialog.Close asChild><button className="icon-button" aria-label="Close dialog"><X size={18} /></button></Dialog.Close></div>{children}</Dialog.Content></Dialog.Portal></Dialog.Root>;
}
export function Empty({ title, children }: { title: string; children?: ReactNode }) { return <div className="empty-state"><div className="empty-icon"><Inbox size={30} /></div><h3>{title}</h3><p>{children}</p></div>; }
export function Loading() { return <div className="loading-state"><LoaderCircle className="spin" size={24} /><span>Loading…</span></div>; }
export function Setting({ label, help, children }: { label: string; help?: string; children: ReactNode }) { return <label className="setting"><span className="setting-label">{label}</span>{children}{help ? <small>{help}</small> : null}</label>; }
export function Toggle({ label, checked, onChange, help }: { label: string; checked: boolean; onChange: (value: boolean) => void; help?: string }) { return <label className="toggle-row"><span>{label}{help ? <small>{help}</small> : null}</span><input className="switch" type="checkbox" checked={checked} onChange={e => onChange(e.target.checked)} /></label>; }
export function Section({ title, children, open = true }: { title: string; children: ReactNode; open?: boolean }) { return <details className="setting-section" open={open}><summary>{title}<ChevronDown size={15} /></summary><div>{children}</div></details>; }

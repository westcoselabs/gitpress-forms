export type Condition = { field: string; operator: 'eq' | 'neq' | 'contains' | 'gt' | 'gte' | 'lt' | 'lte' | 'empty' | 'not_empty'; value: string };
export type Rule = { mode: 'all' | 'any'; rules: Condition[] };
export type Field = {
  id: string; name: string; type: string; label: string; required: boolean;
  placeholder: string; help: string; default: string; width: number;
  options: { label: string; value: string }[]; children: Field[];
  condition?: Rule; min?: number; max?: number; formula?: string; html?: string;
  rows?: string[]; accept?: string; multiple?: boolean; unique?: boolean;
  extension?: Record<string, unknown>; mask?: string; dateMode?: 'date' | 'time' | 'datetime-local'; minLength?: number; maxLength?: number;
};
export type Notification = { attachPdf?: boolean; id: string; to: string; subject: string; message: string; condition?: Rule; cc?: string; bcc?: string; replyTo?: string };
export type FormDefinition = {
  schemaVersion: 1; fields: Field[];
  settings: { pdf?: { title: string; intro: string; footer: string; paper: 'A4' | 'letter' }; entryView?: { enabled: boolean; restricted: boolean; intro: string; background: string }; access?: { restricted: boolean; manageUsers: number[]; entryUsers: number[] }; submitLabel: string; confirmation: string; redirect: string; requireLogin: boolean; maxEntries: number; opensAt: string; closesAt: string; approval: boolean; doubleOptin: boolean; saveResume: boolean; trackPartial?: boolean; mode: 'standard' | 'conversational'; retentionDays: number; notifications: Notification[]; confirmations: { condition: Rule; message: string; redirect: string }[]; css: string; js: string; };
  style: { primary: string; text: string; background: string; border: string; radius: number; gap: number; fontSize: number; maxWidth: number; labelPosition: 'top' | 'left'; };
};
export type FormRecord = { id: number; title: string; slug: string; status: 'draft' | 'published' | 'trash'; definition: FormDefinition; version: number; created_at: string; updated_at: string; entries_count?: number };
export type Entry = { id: number; version: number; form_id: number; status: string; response: Record<string, unknown>; created_at: string; notes: { body: string; by: number; at: string }[] };
export type Bootstrap = { fieldTypes?: import('./definition').CatalogField[]; api: string; nonce: string; site: string; version: string; canManage: boolean; canIntegrate: boolean; canAdmin: boolean; canEntries: boolean; canEditEntries: boolean; };
declare global { interface Window { GitPressFormsAdmin: Bootstrap; } }

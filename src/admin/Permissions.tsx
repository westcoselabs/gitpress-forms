import { useEffect, useState } from 'react';
import type { FormDefinition } from '../shared/types';
import { api } from './api';
import { Setting, Toggle } from './ui';

type Role = { id: string; name: string; forms: boolean; entries: boolean; editEntries: boolean; integrations: boolean };
export function RoleSettings({ notify }: { notify: (text: string, error?: boolean) => void }) {
  const [roles, setRoles] = useState<Role[]>([]); const [busy, setBusy] = useState(false);
  useEffect(() => { if (window.GitPressFormsAdmin.canAdmin) api<Role[]>('permissions').then(setRoles).catch(e => notify(e.message, true)); }, [notify]);
  if (!window.GitPressFormsAdmin.canAdmin) return null;
  return <div className="settings-card"><h2>Role Manager</h2><p>Choose which WordPress roles may manage forms, read entries, and configure connections. Individual forms can further restrict access.</p><table className="data-table"><thead><tr><th>Role</th><th>Forms</th><th>Read Entries</th><th>Edit Entries</th><th>Integrations</th></tr></thead><tbody>{roles.map(role => <tr key={role.id}><td>{role.name}</td>{(['forms', 'entries', 'editEntries', 'integrations'] as const).map(key => <td key={key}><input aria-label={`${role.name}: ${key}`} type="checkbox" checked={role[key]} disabled={role.id === 'administrator'} onChange={e => setRoles(roles.map(r => r.id === role.id ? { ...r, [key]: e.target.checked } : r))} /></td>)}</tr>)}</tbody></table><button className="primary" disabled={busy} onClick={async () => { setBusy(true); try { setRoles(await api<Role[]>('permissions', 'PUT', { roles })); notify('Role permissions saved.'); } catch (e) { notify((e as Error).message, true); } finally { setBusy(false); } }}>Save Role Permissions</button></div>;
}

export function FormAccess({ value, onChange }: { value: FormDefinition['settings']['access']; onChange: (access: NonNullable<FormDefinition['settings']['access']>) => void }) {
  const [users, setUsers] = useState<{ id: number; name: string }[]>([]);
  const [error, setError] = useState('');
  useEffect(() => { if (window.GitPressFormsAdmin.canAdmin) api<{ id: number; name: string }[]>('users').then(setUsers).catch(e => setError(e.message)); }, []);
  if (!window.GitPressFormsAdmin.canAdmin) return null;
  const access = value || { restricted: false, manageUsers: [], entryUsers: [] };
  return <div className="sub-card"><h3>Form Access</h3>{error ? <p role="alert">{error}</p> : null}<Toggle label="Restrict management to selected users" help="Administrators always retain access. Selected users also need the corresponding role permission." checked={access.restricted} onChange={restricted => onChange({ ...access, restricted })} />{access.restricted ? <>{(['manageUsers', 'entryUsers'] as const).map(key => <Setting key={key} label={key === 'manageUsers' ? 'Form Managers' : 'Entry Readers'}><select multiple value={access[key].map(String)} onChange={e => onChange({ ...access, [key]: [...e.target.selectedOptions].map(o => Number(o.value)) })}>{users.map(user => <option key={user.id} value={user.id}>{user.name} (#{user.id})</option>)}</select></Setting>)}</> : null}</div>;
}

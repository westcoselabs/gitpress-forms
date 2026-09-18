import { useEffect, useState } from 'react';
import { api } from './api';
export function EntryHistory({ entryId, version }: { entryId: number; version: number }) {
  const [history, setHistory] = useState<{ version: number; created_by: number; created_at: string; snapshot: unknown }[]>([]);
  const [error, setError] = useState('');
  useEffect(() => { let current = true; api<typeof history>(`entries/${entryId}/history`).then(rows => { if (current) setHistory(rows); }).catch(e => { if (current) setError(e.message); }); return () => { current = false; }; }, [entryId, version]);
  return <details className="sub-card"><summary>Edit History ({history.length})</summary>{error ? <p role="alert">{error}</p> : null}{history.length ? history.map(item => <details key={item.version}><summary>Version {item.version} · {item.created_at} · changed by user #{item.created_by}</summary><pre style={{ whiteSpace: 'pre-wrap' }}>{JSON.stringify(item.snapshot, null, 2)}</pre></details>) : <p>This entry has not been edited.</p>}</details>;
}

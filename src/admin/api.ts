export async function api<T>(path: string, method = 'GET', data?: unknown): Promise<T> {
  const response = await fetch(window.GitPressFormsAdmin.api + path, { method, credentials: 'same-origin', headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': window.GitPressFormsAdmin.nonce }, ...(data !== undefined ? { body: JSON.stringify(data) } : {}) });
  const result = await response.json(); if (!response.ok) throw new Error(result.message || `Request failed (${response.status})`); return result as T;
}
export function download(name: string, data: unknown, type = 'application/json') {
  const blob = new Blob([typeof data === 'string' ? data : JSON.stringify(data, null, 2)], { type }); const url = URL.createObjectURL(blob); const a = document.createElement('a'); a.href = url; a.download = name; a.click(); setTimeout(() => URL.revokeObjectURL(url), 1000);
}

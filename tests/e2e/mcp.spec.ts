import { test, expect } from '@playwright/test';
import { readFileSync } from 'node:fs';
const credentials = JSON.parse(readFileSync('.runtime/mcp-credentials.json', 'utf8'));
const headers = { Authorization: `Basic ${Buffer.from(`${credentials.user}:${credentials.password}`).toString('base64')}`, Accept: 'application/json, text/event-stream', 'MCP-Protocol-Version': '2025-11-25' };
const endpoint = '/wp-json/gitpress-forms/v1/mcp';
test('MCP authenticates with WordPress application passwords and advertises permitted tools', async ({ request }) => {
  const anonymous = await request.post(endpoint, { data: { jsonrpc: '2.0', id: 1, method: 'initialize' } }); expect([401, 403]).toContain(anonymous.status());
  const init = await request.post(endpoint, { headers, data: { jsonrpc: '2.0', id: 1, method: 'initialize', params: { protocolVersion: '2025-11-25', capabilities: {}, clientInfo: { name: 'acceptance', version: '1' } } } });
  expect(init.ok()).toBeTruthy(); expect((await init.json()).result.serverInfo.name).toBe('gitpress-forms');
  const ready = await request.post(endpoint, { headers, data: { jsonrpc: '2.0', method: 'notifications/initialized' } }); expect(ready.status()).toBe(202); expect(await ready.text()).toBe('');
  const list = await request.post(endpoint, { headers, data: { jsonrpc: '2.0', id: 2, method: 'tools/list' } });
  const names = (await list.json()).result.tools.map((t: { name: string }) => t.name); expect(names).toContain('form_create'); expect(names).toContain('entries_list'); expect(names).not.toContain('entry_update'); expect(names).not.toContain('job_retry');
  const wrongOrigin = await request.post(endpoint, { headers: { ...headers, Origin: 'https://unrelated.example' }, data: { jsonrpc: '2.0', id: 3, method: 'ping' } }); expect(wrongOrigin.status()).toBe(403);
  const version = await request.post(endpoint, { headers: { ...headers, 'MCP-Protocol-Version': 'invalid' }, data: { jsonrpc: '2.0', id: 4, method: 'ping' } }); expect(version.status()).toBe(400);
  const get = await request.get(endpoint, { headers }); expect(get.status()).toBe(405);
});
test('MCP calls use the same capability and argument validation as management REST', async ({ request }) => {
  const denied = await request.post(endpoint, { headers, data: { jsonrpc: '2.0', id: 1, method: 'tools/call', params: { name: 'entry_update', arguments: { id: 1, version: 1, status: 'trash' } } } }); expect((await denied.json()).result.isError).toBe(true);
  const read = await request.post(endpoint, { headers, data: { jsonrpc: '2.0', id: 2, method: 'tools/call', params: { name: 'forms_list', arguments: {} } } }); expect((await read.json()).result.isError).toBe(false);
  const invalid = await request.post(endpoint, { headers, data: { jsonrpc: '2.0', id: 3, method: 'tools/call', params: { name: 'form_read', arguments: { id: '../permissions' } } } }); expect((await invalid.json()).error.code).toBe(-32602);
});

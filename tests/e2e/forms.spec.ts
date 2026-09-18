import { test, expect } from '@playwright/test';
import { emptyDefinition, makeField } from '../../src/shared/definition';
import type { FormDefinition, FormRecord } from '../../src/shared/types';
import { login, adminApi, create, session } from './support';

test('builder creates, publishes, renders and stores a real public submission', async ({ page, browser }) => {
  const errors: string[] = []; page.on('pageerror', error => errors.push(error.message));
  await login(page); await page.screenshot({ path: '.runtime/verified-dashboard.png', fullPage: true });
  await page.getByRole('button', { name: 'Add New Form', exact: true }).click();
  const title = `Contact acceptance ${Date.now()}`;
  await page.getByLabel('Form Name', { exact: true }).fill(title); await page.getByRole('button', { name: 'Create Form', exact: true }).click();
  await expect(page.getByLabel('Form title')).toHaveValue(title);
  await page.getByRole('combobox', { name: 'Form status' }).selectOption('published');
  await page.getByRole('button', { name: 'Save Form', exact: true }).click(); await expect(page.getByRole('status').filter({ hasText: 'Form saved.' })).toBeVisible();
  await page.screenshot({ path: '.runtime/verified-builder.png', fullPage: true });
  const forms = await adminApi<FormRecord[]>(page, 'forms'); const form = forms.find(f => f.title === title)!;
  const context = await browser.newContext(); const visitor = await context.newPage(); await visitor.goto(`${new URL(page.url()).origin}/?gitpress_form=${form.id}`);
  await visitor.getByLabel('First name', { exact: true }).fill('Casey'); await visitor.getByLabel('Last name', { exact: true }).fill('Tester'); await visitor.getByLabel('Email Address', { exact: false }).fill('casey@example.test'); await visitor.getByLabel('Your Message').fill('Verified browser submission');
  await visitor.getByRole('button', { name: 'Submit Form', exact: true }).click(); await expect(visitor.getByRole('status')).toContainText('Thank you!');
  const entries = await adminApi<{ items: { response: Record<string, unknown> }[] }>(page, `entries?form=${form.id}`);
  expect(entries.items).toHaveLength(1); expect(Object.values(entries.items[0].response)).toContain('Verified browser submission');
  await visitor.screenshot({ path: '.runtime/verified-submission.png', fullPage: true }); await context.close(); expect(errors).toEqual([]);
});

test('server enforces conditional pruning, recalculation, validation and idempotency', async ({ page, request }) => {
  await login(page); const definition = emptyDefinition();
  const choice = { ...makeField('select'), name: 'choice', options: [{ label: 'No', value: 'no' }, { label: 'Yes', value: 'yes' }] };
  const email = { ...makeField('email'), name: 'email', required: true };
  const hidden = { ...makeField('text'), name: 'detail', required: true, condition: { mode: 'all' as const, rules: [{ field: 'choice', operator: 'eq' as const, value: 'yes' }] } };
  definition.fields = [choice, email, hidden, { ...makeField('number'), name: 'quantity' }, { ...makeField('calculation'), name: 'total', formula: '{quantity}*5' }];
  const form = await create(page, definition); const s = await session(request, form.id); const key = crypto.randomUUID();
  const payload = { token: s.token, idempotencyKey: key, values: { choice: 'no', email: 'valid@example.test', detail: 'must not persist', quantity: '3', total: -100, is_admin: true } };
  const route = `/wp-json/gitpress-forms/v1/forms/${form.id}/submit`;
  const first = await request.post(route, { data: payload }); expect(first.status()).toBe(200); expect((await first.json()).success).toBe(true);
  const retry = await request.post(route, { data: payload }); expect((await retry.json()).success).toBe(true);
  const entries = await adminApi<{ items: { response: Record<string, unknown> }[] }>(page, `entries?form=${form.id}`);
  expect(entries.items).toHaveLength(1); expect(entries.items[0].response.total).toBe(15); expect(entries.items[0].response).not.toHaveProperty('detail'); expect(entries.items[0].response).not.toHaveProperty('is_admin');
  const invalid = await request.post(route, { data: { ...payload, idempotencyKey: crypto.randomUUID(), values: { ...payload.values, email: 'invalid' } } }); expect((await invalid.json()).errors.email).toBeTruthy();
  const forged = await request.post(route, { data: { ...payload, token: 'forged' } }); expect(forged.status()).toBe(403);
  const denied = await request.get(`/wp-json/gitpress-forms/v1/entries?form=${form.id}`); expect([401, 403]).toContain(denied.status());
});

test('save/resume uses private tokens and revisions reject stale overwrites', async ({ page, request }) => {
  await login(page); const definition = emptyDefinition(); definition.settings.saveResume = true; definition.fields = [{ ...makeField('email'), name: 'email', required: true }]; const form = await create(page, definition);
  const s = await session(request, form.id); const saved = await request.post(`/wp-json/gitpress-forms/v1/forms/${form.id}/progress`, { data: { token: s.token, values: { email: 'resume@example.test' } } }); expect(saved.ok()).toBeTruthy(); const token = (await saved.json()).token;
  const resumed = await request.post(`/wp-json/gitpress-forms/v1/forms/${form.id}/resume`, { data: { resumeToken: token } }); expect((await resumed.json()).values.email).toBe('resume@example.test');
  const wrongForm = await create(page, definition); const invalid = await request.post(`/wp-json/gitpress-forms/v1/forms/${wrongForm.id}/resume`, { data: { resumeToken: token } }); expect(invalid.status()).toBe(404);
  const savedForm = await adminApi<FormRecord>(page, `forms/${form.id}`, 'PUT', { ...form, title: 'Updated title' }); expect(savedForm.version).toBe(2);
  await expect(adminApi(page, `forms/${form.id}`, 'PUT', form)).rejects.toThrow('updated elsewhere');
  const revisions = await adminApi<{ id: number; version: number }[]>(page, `forms/${form.id}/revisions`); expect(revisions).toHaveLength(2);
  const restored = await adminApi<FormRecord>(page, `forms/${form.id}/revisions/${revisions[1].id}`, 'POST', {}); expect(restored.version).toBe(3);
});

test('required repeater rows are checked and admin approval gates notifications', async ({ page, request }) => {
  await login(page); const definition = emptyDefinition(); definition.settings.approval = true; definition.settings.notifications = [{ id: 'team', to: 'team@example.test', subject: 'Test entry', message: '{all_fields}' }]; definition.fields = [{ ...makeField('repeat'), name: 'people', children: [{ ...makeField('email'), name: 'email', required: true }] }];
  const form = await create(page, definition); const s = await session(request, form.id); const route = `/wp-json/gitpress-forms/v1/forms/${form.id}/submit`;
  const bad = await request.post(route, { data: { token: s.token, idempotencyKey: crypto.randomUUID(), values: { people: [{ email: 'bad' }] } } }); expect((await bad.json()).errors['people.0.email']).toBeTruthy();
  const good = await request.post(route, { data: { token: s.token, idempotencyKey: crypto.randomUUID(), values: { people: [{ email: 'a@example.test' }, { email: 'b@example.test' }] } } }); expect((await good.json()).success).toBe(true);
  const entries = await adminApi<{ items: { id: number; status: string }[] }>(page, `entries?form=${form.id}`); const entry = entries.items[0]; expect(entry.status).toBe('pending_approval');
  let jobs = await adminApi<{ entry_id: string; status: string }[]>(page, 'jobs'); expect(jobs.filter(j => Number(j.entry_id) === entry.id)).toHaveLength(0);
  await adminApi(page, `entries/${entry.id}`, 'PUT', { status: 'approved' }); jobs = await adminApi(page, 'jobs'); expect(jobs.filter(j => Number(j.entry_id) === entry.id)).toHaveLength(1);
});

test('GitPress export and import preserves definitions and does not duplicate repeated imports', async ({ page }) => {
  await login(page); const definition = emptyDefinition(); definition.fields = [makeField('text'), makeField('email')]; definition.style.primary = '#112233'; const form = await create(page, definition);
  const data = { source: `test_${form.id}`, forms: [form] }; const preview = await adminApi<{ forms: { definition: FormDefinition }[] }>(page, 'import/preview', 'POST', data); expect(preview.forms[0].definition.style.primary).toBe('#112233');
  const first = await adminApi<{ forms: { id: number }[] }>(page, 'import', 'POST', data); const second = await adminApi<{ forms: { id: number; alreadyImported: boolean }[] }>(page, 'import', 'POST', data); expect(first.forms[0].id).toBe(second.forms[0].id); expect(second.forms[0].alreadyImported).toBe(true);
});

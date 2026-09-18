import { test, expect } from '@playwright/test';
import { emptyDefinition, makeField } from '../../src/shared/definition';
import { login, adminApi, create, session } from './support';
import type { Entry } from '../../src/shared/types';

test('partial entries deduplicate progress, resume and become one completed submission', async ({ page, request }) => {
  await login(page); const definition = emptyDefinition(); definition.settings.trackPartial = true;
  definition.settings.notifications = [{ id: 'partial_test', to: 'test@example.test', subject: 'Completed', message: '{all_fields}' }];
  definition.fields = [{ ...makeField('email'), name: 'email', required: true }, { ...makeField('text'), name: 'answer', required: true }];
  const form = await create(page, definition); const s = await session(request, form.id); const idempotencyKey = crypto.randomUUID();
  const route = `/wp-json/gitpress-forms/v1/forms/${form.id}`;
  const payload = { token: s.token, idempotencyKey, values: { email: 'partial@example.test' } };
  const saved = await request.post(`${route}/progress`, { data: payload }); expect(saved.ok()).toBeTruthy(); const resumeToken = (await saved.json()).token;
  expect((await request.post(`${route}/progress`, { data: payload })).ok()).toBeTruthy();
  let entries = await adminApi<{ items: Entry[] }>(page, `entries?form=${form.id}`); expect(entries.items).toHaveLength(1); const partial = entries.items[0]; expect(partial.status).toBe('partial');
  await expect(adminApi(page, `entries/${partial.id}`, 'PUT', { status: 'read' })).rejects.toThrow('must be completed');
  let jobs = await adminApi<{ entry_id: number }[]>(page, 'jobs'); expect(jobs.filter(j => Number(j.entry_id) === partial.id)).toHaveLength(0);
  const resumed = await request.post(`${route}/resume`, { data: { resumeToken } }); expect((await resumed.json()).values.email).toBe('partial@example.test');
  const submission = { ...payload, resumeToken, values: { ...payload.values, answer: 'Completed' } };
  const completed = await request.post(`${route}/submit`, { data: submission }); expect((await completed.json()).success).toBe(true);
  expect((await (await request.post(`${route}/submit`, { data: submission })).json()).success).toBe(true);
  entries = await adminApi(page, `entries?form=${form.id}`); expect(entries.items).toHaveLength(1); expect(entries.items[0].id).toBe(partial.id); expect(entries.items[0].status).toBe('unread'); expect(entries.items[0].version).toBeGreaterThan(partial.version);
  jobs = await adminApi(page, 'jobs'); expect(jobs.filter(j => Number(j.entry_id) === partial.id)).toHaveLength(1);
  expect((await request.post(`${route}/resume`, { data: { resumeToken } })).status()).toBe(409);
});

test('advanced public controls persist ratings, ranking order, rich text, masks, grids and nested rows', async ({ page, browser }) => {
  await login(page); const definition = emptyDefinition();
  definition.fields = [
    { ...makeField('rating'), name: 'rating', label: 'Experience', max: 5, required: true },
    { ...makeField('nps'), name: 'nps', label: 'Recommend', required: true },
    { ...makeField('ranking'), name: 'ranking', label: 'Rank services' },
    { ...makeField('richtext'), name: 'rich', label: 'Details', required: true },
    { ...makeField('mask'), name: 'mask', label: 'Reference', mask: 'aa-9999', required: true },
    { ...makeField('grid'), name: 'grid', label: 'Score each service', rows: ['Cut', 'Shave'], required: true },
    { ...makeField('repeat'), name: 'groups', label: 'Groups', children: [{ ...makeField('repeat'), name: 'people', label: 'People', children: [{ ...makeField('email'), name: 'person_email', label: 'Person email', required: true }] }] },
  ];
  const form = await create(page, definition, 'Advanced controls'); const context = await browser.newContext(); const visitor = await context.newPage();
  await visitor.goto(`${new URL(page.url()).origin}/?gitpress_form=${form.id}`);
  await visitor.getByRole('radiogroup', { name: 'Experience' }).getByRole('radio', { name: '4', exact: true }).check();
  await visitor.getByRole('radiogroup', { name: 'Recommend' }).getByRole('radio', { name: '9', exact: true }).check();
  await visitor.getByRole('button', { name: 'Move Third Choice up' }).click();
  await visitor.getByRole('textbox', { name: 'Details' }).fill('Rich text answer');
  await visitor.getByLabel(/Reference/).fill('AB1234'); await expect(visitor.getByLabel(/Reference/)).toHaveValue('AB-1234');
  await visitor.getByRole('radio', { name: 'Cut: First Choice' }).check(); await visitor.getByRole('radio', { name: 'Shave: Second Choice' }).check();
  await visitor.getByLabel('Person email', { exact: false }).fill('one@example.test');
  await visitor.locator('[data-field=people]').getByRole('button', { name: 'Add row', exact: true }).click();
  await visitor.getByLabel('Person email', { exact: false }).nth(1).fill('two@example.test');
  const ids = await visitor.locator('[id^=gpf-]').evaluateAll(elements => elements.map(e => e.id)); expect(new Set(ids).size).toBe(ids.length);
  await visitor.getByRole('button', { name: 'Submit Form' }).click(); await expect(visitor.getByRole('status')).toContainText('Thank you!');
  const { items } = await adminApi<{ items: Entry[] }>(page, `entries?form=${form.id}`); const response = items[0].response;
  expect(response.rating).toBe(4); expect(response.nps).toBe(9); expect(response.ranking).toEqual(['option_1', 'option_3', 'option_2']); expect(response.mask).toBe('AB-1234'); expect(response.grid).toEqual(['option_1', 'option_2']); expect(response.groups).toEqual([{ people: [{ person_email: 'one@example.test' }, { person_email: 'two@example.test' }] }]);
  await context.close();
});

test('uploads stay encrypted and require entry permissions to download', async ({ page, request }) => {
  await login(page); const definition = emptyDefinition(); definition.fields = [{ ...makeField('file'), name: 'attachment', label: 'Attachment', required: true, accept: '.txt,.png' }]; const form = await create(page, definition); const s = await session(request, form.id);
  const upload = await request.post(`/wp-json/gitpress-forms/v1/forms/${form.id}/upload`, { multipart: { token: s.token, field: 'attachment', file: { name: 'private.txt', mimeType: 'text/plain', buffer: Buffer.from('Private acceptance content') } } }); expect(upload.ok()).toBeTruthy(); const file = await upload.json();
  const bad = await request.post(`/wp-json/gitpress-forms/v1/forms/${form.id}/upload`, { multipart: { token: s.token, field: 'attachment', file: { name: 'shell.php', mimeType: 'application/x-php', buffer: Buffer.from('<?php echo 1;') } } }); expect(bad.status()).toBe(400);
  const submitted = await request.post(`/wp-json/gitpress-forms/v1/forms/${form.id}/submit`, { data: { token: s.token, idempotencyKey: crypto.randomUUID(), values: { attachment: file.token } } }); expect((await submitted.json()).success).toBe(true);
  const { items } = await adminApi<{ items: Entry[] }>(page, `entries?form=${form.id}`); const files = await adminApi<{ url: string }[]>(page, `entries/${items[0].id}/files`); expect(files).toHaveLength(1);
  const privateFile = await page.request.get(files[0].url); expect(await privateFile.text()).toBe('Private acceptance content'); expect(privateFile.headers()['content-disposition']).toContain('attachment');
  const denied = await request.get(files[0].url); expect(await denied.text()).not.toBe('Private acceptance content');
  const replay = await request.post(`/wp-json/gitpress-forms/v1/forms/${form.id}/submit`, { data: { token: s.token, idempotencyKey: crypto.randomUUID(), values: { attachment: file.token } } }); expect(replay.status()).toBe(400);
});

test('entry edit history, PDF export and tokenized frontend view work with real records', async ({ page, request }) => {
  await login(page); const definition = emptyDefinition(); definition.fields = [{ ...makeField('text'), name: 'answer', label: 'Answer' }]; definition.settings.entryView = { enabled: true, restricted: false, intro: 'Your submission', background: '#ffffff' };
  const form = await create(page, definition); const s = await session(request, form.id); const result = await request.post(`/wp-json/gitpress-forms/v1/forms/${form.id}/submit`, { data: { token: s.token, idempotencyKey: crypto.randomUUID(), values: { answer: 'Initial answer' } } }); const confirmation = await result.json();
  const view = await request.get(confirmation.entryView); expect(view.ok()).toBeTruthy(); expect(await view.text()).toContain('Initial answer'); expect(view.headers()['x-robots-tag']).toContain('noindex');
  const { items } = await adminApi<{ items: Entry[] }>(page, `entries?form=${form.id}`); const entry = items[0];
  const changed = await adminApi<Entry>(page, `entries/${entry.id}`, 'PUT', { version: entry.version, response: { answer: 'Updated answer' } }); expect(changed.version).toBe(2);
  await expect(adminApi(page, `entries/${entry.id}`, 'PUT', { version: entry.version, note: 'Stale edit' })).rejects.toThrow('changed elsewhere');
  const history = await adminApi<{ snapshot: { response: { answer: string } } }[]>(page, `entries/${entry.id}/history`); expect(history[0].snapshot.response.answer).toBe('Initial answer');
  const pdf = await adminApi<{ url: string }>(page, `entries/${entry.id}/pdf`); const document = await page.request.get(pdf.url); expect(document.headers()['content-type']).toContain('application/pdf'); expect((await document.body()).subarray(0, 5).toString()).toBe('%PDF-');
});

test('file upload renews an expired cached session and retains the selected file', async ({ page, browser }) => {
  await login(page); const definition = emptyDefinition(); definition.fields = [{ ...makeField('file'), name: 'attachment', label: 'Attachment', required: true, accept: '.txt' }];
  const form = await create(page, definition); const context = await browser.newContext(); const visitor = await context.newPage(); let sessions = 0;
  await visitor.route('**/admin-ajax.php?action=gitpress_forms_session', route => {
    sessions++; return sessions === 1 ? route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ token: 'expired-cached-token', nonce: '' }) }) : route.continue();
  });
  await visitor.goto(`${new URL(page.url()).origin}/?gitpress_form=${form.id}`);
  await visitor.getByLabel(/Attachment/).setInputFiles({ name: 'session.txt', mimeType: 'text/plain', buffer: Buffer.from('Renewal acceptance') });
  await expect(visitor.locator('.gpf-upload-status')).toContainText('session.txt uploaded'); expect(sessions).toBe(2);
  await visitor.getByRole('button', { name: 'Submit Form' }).click(); await expect(visitor.getByRole('status')).toContainText('Thank you!');
  await context.close();
});

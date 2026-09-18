import { test, expect, type Page, type APIRequestContext } from '@playwright/test';
import { emptyDefinition, makeField } from '../../src/shared/definition';
import type { FormDefinition, FormRecord } from '../../src/shared/types';

export async function login(page: Page) {
  await page.goto('/wp-admin/admin.php?page=gitpress-forms');
  if (page.url().includes('wp-login')) {
    // WordPress schedules its initial focus. Wait for it before entering credentials.
    const username = page.getByLabel('Username or Email Address');
    await expect(username).toBeFocused();
    await username.fill('gpf_admin'); await page.getByLabel('Password', { exact: true }).fill('gitpress-test-only');
    await expect(username).toHaveValue('gpf_admin');
    await page.getByRole('button', { name: 'Log In', exact: true }).click();
  }
  await expect(page.getByRole('heading', { name: 'All Forms', exact: true })).toBeVisible();
}
export async function adminApi<T>(page: Page, path: string, method = 'GET', body?: unknown): Promise<T> {
  return page.evaluate(async ({ path, method, body }) => { const r = await fetch(window.GitPressFormsAdmin.api + path, { method, headers: { 'X-WP-Nonce': window.GitPressFormsAdmin.nonce, 'Content-Type': 'application/json' }, ...(body === undefined ? {} : { body: JSON.stringify(body) }) }); const result = await r.json(); if (!r.ok) throw new Error(result.message); return result; }, { path, method, body });
}
export async function create(page: Page, definition: FormDefinition, title = 'Acceptance Form') { return adminApi<FormRecord>(page, 'forms', 'POST', { title: `${title} ${Date.now()}`, status: 'published', definition }); }
export async function session(request: APIRequestContext, id: number) { const r = await request.post('/wp-admin/admin-ajax.php?action=gitpress_forms_session', { form: { form_id: String(id) } }); expect(r.ok()).toBeTruthy(); return r.json(); }

import { test, expect } from '@playwright/test';
import { readFileSync } from 'node:fs';

const fixturePath = '.runtime/fixtures.json';
const fixtures = JSON.parse(readFileSync(fixturePath, 'utf8')) as { formId: number; pages: Record<string, number> };
for (const mode of ['theme_wrapped', 'full_canvas', 'gitpress_managed', 'stale']) {
  test(`GitPress ${mode}: cached nested form loads styles and submits`, async ({ page }) => {
    const errors: string[] = []; page.on('pageerror', e => errors.push(e.message));
    await page.goto(`/?page_id=${fixtures.pages[mode]}`);
    const form = page.locator('form[data-gpf-form]'); await expect(form).toHaveAttribute('data-gpf-ready', 'true');
    await expect(page.getByRole('heading', { name: 'GitPress cached fragment' })).toBeVisible();
    if (mode === 'stale') await expect(page.locator('.dgs-content-block')).toHaveClass(/is-stale/);
    expect(await form.evaluate(el => getComputedStyle(el).getPropertyValue('--gpf-primary').trim())).toBe('#3865e9');
    await form.getByLabel('Email Address').fill(`${mode}@example.test`); await form.getByRole('button', { name: 'Submit Form' }).click();
    await expect(form.getByRole('status')).toContainText('Thank you!'); expect(errors).toEqual([]);
  });
}
test('multiple instances have distinct IDs and independently submit after session refresh', async ({ page }) => {
  await page.goto(`/?page_id=${fixtures.pages.multiple}`);
  const forms = page.locator('form[data-gpf-form]'); await expect(forms).toHaveCount(2);
  const ids = await page.locator('[id^=gpf-]').evaluateAll(elements => elements.map(el => el.id)); expect(new Set(ids).size).toBe(ids.length);
  let sessions = 0;
  await page.route('**/wp-admin/admin-ajax.php?action=gitpress_forms_session', async route => {
    sessions++;
    if (sessions === 1) await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ token: 'expired-token', nonce: '' }) });
    else await route.continue();
  });
  for (let i = 0; i < 2; i++) { const form = forms.nth(i); await form.getByLabel('Email Address').fill(`multiple-${i}@example.test`); await form.getByRole('button', { name: 'Submit Form' }).click(); await expect(form.getByRole('status')).toContainText('Thank you!'); }
  expect(sessions).toBe(3);
});

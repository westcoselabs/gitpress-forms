import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';
import { readFileSync } from 'node:fs';
import { emptyDefinition, makeField } from '../../src/shared/definition';
import { login, create } from './support';
const fixtures = JSON.parse(readFileSync('.runtime/fixtures.json', 'utf8'));

test('popup form traps focus, closes with Escape and submits', async ({ page }) => {
  await page.goto(`/?page_id=${fixtures.pages.popup}`); const trigger = page.getByRole('button', { name: 'Contact us', exact: true });
  await trigger.click(); const dialog = page.getByRole('dialog'); await expect(dialog).toBeVisible();
  await page.keyboard.press('Escape'); await expect(dialog).not.toBeVisible(); await expect(trigger).toBeFocused();
  await trigger.click(); await dialog.getByLabel(/Email Address/).fill('popup@example.test'); await dialog.getByRole('button', { name: 'Submit Form', exact: true }).click();
  await expect(dialog.getByRole('status')).toContainText('Thank you!');
});

test('Gutenberg style overrides render without changing the saved form', async ({ page }) => {
  await page.goto(`/?page_id=${fixtures.pages.block}`); const form = page.locator('form[data-gpf-form]'); await expect(form).toBeVisible();
  expect(await form.evaluate(el => getComputedStyle(el).getPropertyValue('--gpf-primary'))).toBe('#ac2255');
  expect(await form.evaluate(el => getComputedStyle(el).getPropertyValue('--gpf-radius'))).toBe('12px');
  await page.goto(`/?gitpress_form=${fixtures.formId}`);
  expect(await form.evaluate(el => getComputedStyle(el).getPropertyValue('--gpf-radius'))).not.toBe('12px');
});

test('public form meets automated WCAG AA checks at mobile width and keyboard advances steps', async ({ page, browser }) => {
  await login(page); const definition = emptyDefinition();
  definition.fields = [
    { ...makeField('step'), label: 'Contact', children: [{ ...makeField('email'), name: 'email', label: 'Email', required: true }] },
    { ...makeField('step'), label: 'Details', children: [{ ...makeField('textarea'), name: 'details', label: 'Details', required: true }] },
  ]; const form = await create(page, definition);
  const context = await browser.newContext({ viewport: { width: 390, height: 844 } }); const visitor = await context.newPage();
  await visitor.goto(`${new URL(page.url()).origin}/?gitpress_form=${form.id}`);
  const audit = await new AxeBuilder({ page: visitor }).include('.gpf-form').withTags(['wcag2a', 'wcag2aa', 'wcag21aa']).analyze();
  expect(audit.violations.map(v => ({ id: v.id, nodes: v.nodes.map(n => n.target) }))).toEqual([]);
  expect(await visitor.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
  await visitor.getByLabel('Email', { exact: false }).fill('steps@example.test'); await visitor.keyboard.press('Enter');
  await expect(visitor.getByRole('textbox', { name: 'Details', exact: false })).toBeFocused();
  await visitor.getByRole('textbox', { name: 'Details', exact: false }).fill('Keyboard completion');
  await visitor.getByRole('button', { name: 'Submit Form' }).click(); await expect(visitor.getByRole('status')).toContainText('Thank you!');
  await context.close();
});

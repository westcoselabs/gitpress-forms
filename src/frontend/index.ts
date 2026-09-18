import { matches, calculate } from '../shared/logic';
import { flatten } from '../shared/definition';
import type { Field } from '../shared/types';

type Config = { id: number; api: string; instance: string; sessionUrl: string; fields: Field[]; settings: { saveResume: boolean; trackPartial?: boolean; mode: string; submitLabel: string }; preview: boolean };
type Values = Record<string, unknown>;
type RecaptchaClient = { render: (container: HTMLElement, options: Record<string, unknown>) => number; reset: (widgetId?: number) => void };
const escape = CSS.escape;
let recaptchaLoader: Promise<RecaptchaClient> | null = null;
function loadRecaptcha(): Promise<RecaptchaClient> {
  const current = (window as unknown as { grecaptcha?: RecaptchaClient }).grecaptcha;
  if (typeof current?.render === 'function') return Promise.resolve(current);
  if (recaptchaLoader) return recaptchaLoader;
  recaptchaLoader = new Promise((resolve, reject) => {
    let attempts = 0;
    const ready = () => {
      const client = (window as unknown as { grecaptcha?: RecaptchaClient }).grecaptcha;
      if (typeof client?.render === 'function') { resolve(client); return; }
      if (++attempts < 200) { window.setTimeout(ready, 25); return; }
      reject(new Error('Google reCAPTCHA did not initialize.'));
    };
    const existing = document.querySelector<HTMLScriptElement>('script[src*="recaptcha/api.js"]');
    if (existing) { existing.addEventListener('error', () => reject(new Error('Google reCAPTCHA could not be loaded.')), { once: true }); ready(); return; }
    const script = document.createElement('script'); script.src = 'https://www.google.com/recaptcha/api.js?render=explicit'; script.async = true; script.defer = true; script.dataset.gpfRecaptcha = 'true'; script.addEventListener('load', ready, { once: true }); script.addEventListener('error', () => reject(new Error('Google reCAPTCHA could not be loaded.')), { once: true }); document.head.appendChild(script);
  });
  return recaptchaLoader;
}
function boot(form: HTMLFormElement) {
  if (form.dataset.gpfReady) return;
  form.dataset.gpfReady = 'true';
  const config: Config = JSON.parse(form.querySelector('.gpf-config')!.textContent || '{}');
  const alert = form.querySelector<HTMLElement>('.gpf-alert')!;
  const submit = form.querySelector<HTMLButtonElement>('.gpf-submit')!;
  const next = form.querySelector<HTMLButtonElement>('.gpf-next')!;
  const back = form.querySelector<HTMLButtonElement>('.gpf-back')!;
  let session = { token: '', nonce: '' }; let idempotencyKey = crypto.randomUUID(); let pendingUploads = 0; let step = 0;
  let resumeToken = '';
  const node = (field: Field, scope: ParentNode = form) => scope.querySelector<HTMLElement>(`[data-field="${escape(field.name)}"]`);
  function read(field: Field, scope: ParentNode): unknown {
    const el = node(field, scope); if (!el) return '';
    if (['name', 'address', 'grid'].includes(field.type)) {
      const result: Values = {};
      el.querySelectorAll<HTMLInputElement>('input').forEach(input => { if (input.type !== 'radio' || input.checked) { const key = input.name.match(/\[([^\]]+)\]$/)?.[1]; if (key) result[key] = input.value; } }); return result;
    }
    if (field.type === 'checkbox') return [...el.querySelectorAll<HTMLInputElement>('input:checked')].map(i => i.value);
    if (['radio', 'rating', 'nps'].includes(field.type)) return el.querySelector<HTMLInputElement>('input:checked')?.value || '';
    if (field.type === 'terms' || field.type === 'gdpr') return el.querySelector<HTMLInputElement>('input:checked') ? '1' : '';
    if (field.type === 'multiselect') return [...(el.querySelector('select')?.selectedOptions || [])].map(o => o.value).filter(Boolean);
    if (field.type === 'ranking') return [...el.querySelectorAll<HTMLElement>('.gpf-ranking > li')].map(li => li.dataset.value!);
    if (field.type === 'richtext') return el.querySelector<HTMLElement>('.gpf-rich-editor')?.innerHTML || '';
    if (field.type === 'file' || field.type === 'image' || field.type === 'signature') return el.querySelector<HTMLInputElement>('input[type=hidden]')?.value || '';
    return el.querySelector<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>('input,textarea,select')?.value || '';
  }
  function collect(fields = config.fields, scope: ParentNode = form): Values {
    const result: Values = {};
    for (const field of fields) {
      const el = node(field, scope); if (!el || el.dataset.conditionHidden === 'true') continue;
      if (field.type === 'repeat') result[field.name] = [...el.querySelectorAll<HTMLElement>(':scope > .gpf-repeat-rows > .gpf-repeat-row')].map(row => collect(field.children, row));
      else if (field.children.length) Object.assign(result, collect(field.children, el));
      else if (!['html', 'section', 'shortcode'].includes(field.type)) result[field.name] = read(field, scope);
    }
    return result;
  }
  function message(text: string, error = false) { alert.textContent = text; alert.hidden = false; alert.classList.toggle('gpf-alert-error', error); alert.focus({ preventScroll: true }); }
  function applyValues(values: Values, fields = config.fields, scope: ParentNode = form) {
    for (const field of fields) {
      const el = node(field, scope); if (!el) continue; const value = values[field.name];
      if (field.type === 'repeat' && Array.isArray(value)) {
        el.querySelector('.gpf-repeat-rows')!.replaceChildren(); value.forEach(row => { const element = addRow(el); if (element) applyValues(row as Values, field.children, element); });
      } else if (field.children.length) applyValues(values, field.children, el);
      else if (value !== undefined && field.type !== 'password') {
        if (field.type === 'richtext') { const editor = el.querySelector<HTMLElement>('.gpf-rich-editor'); if (editor) editor.innerHTML = String(value); }
        if (field.type === 'ranking' && Array.isArray(value)) { const list = el.querySelector('.gpf-ranking')!; for (const item of value) { const row = list.querySelector(`li[data-value="${escape(String(item))}"]`); if (row) list.appendChild(row); } }
        el.querySelectorAll<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>('input,select,textarea').forEach(input => {
          if (input instanceof HTMLInputElement && input.type === 'file') return;
          if (input instanceof HTMLInputElement && ['radio', 'checkbox'].includes(input.type)) {
            const key = input.name.match(/\[([^\]]+)\]$/)?.[1]; const v = field.type === 'grid' && key ? (value as Values)[key] : value;
            input.checked = Array.isArray(v) ? v.map(String).includes(input.value) : String(v) === input.value;
          } else if (input instanceof HTMLSelectElement && input.multiple) { [...input.options].forEach(o => { o.selected = Array.isArray(value) && value.map(String).includes(o.value); }); }
          else if (typeof value === 'object' && value !== null && !Array.isArray(value)) { const key = input.name.match(/\[([^\]]+)\]$/)?.[1]; if (key) input.value = String((value as Values)[key] ?? ''); }
          else input.value = String(value);
        });
      }
    }
  }
  function update(fields = config.fields, scope: ParentNode = form, outer: Values = {}) {
    const values = { ...outer, ...collect(fields, scope) };
    for (const field of fields) {
      const el = node(field, scope); if (!el) continue;
      const visible = matches(field.condition, values); el.dataset.conditionHidden = String(!visible);
      if (!el.dataset.stepIndex) el.hidden = !visible || field.type === 'hidden';
      if (field.type === 'calculation' && visible) { try { const value = calculate(field.formula || '0', values); const input = el.querySelector('input'); if (input) input.value = String(value); values[field.name] = value; } catch { const input = el.querySelector('input'); if (input) input.value = ''; } }
      if (field.type === 'repeat') el.querySelectorAll<HTMLElement>(':scope > .gpf-repeat-rows > .gpf-repeat-row').forEach(row => update(field.children, row, values));
      else if (field.children.length) update(field.children, el, values);
    }
  }
  const allSteps = config.settings.mode === 'conversational'
    ? [...form.querySelectorAll<HTMLElement>(':scope > .gpf-fields > .gpf-field')].filter(el => !['hidden', 'section'].includes(el.dataset.type || ''))
    : [...form.querySelectorAll<HTMLElement>('.gpf-type-step')];
  allSteps.forEach((el, index) => { el.dataset.stepIndex = String(index + 1); });
  function steps() { return allSteps.filter(el => el.dataset.conditionHidden !== 'true'); }
  function showStep() {
    const visible = steps(); if (!visible.length) return;
    step = Math.min(step, visible.length - 1);
    allSteps.forEach(el => { el.hidden = el !== visible[step]; });
    back.hidden = step === 0; next.hidden = step >= visible.length - 1; submit.hidden = !next.hidden;
    form.querySelector('.gpf-step-label')!.textContent = `${step + 1} of ${visible.length}`;
    const progress = form.querySelector<HTMLElement>('.gpf-progress')!; progress.hidden = false; progress.querySelector<HTMLElement>('span')!.style.width = `${(step + 1) / visible.length * 100}%`;
  }
  function refresh() { for (let pass = 0; pass < Math.min(flatten(config.fields).length + 1, 30); pass++) update(); showStep(); }
  function validStep(): boolean {
    const current = steps()[step]; if (!current) return true;
    const inputs = current.querySelectorAll<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>('input,select,textarea');
    for (const input of inputs) { if (!input.closest('[data-condition-hidden="true"]') && !input.checkValidity()) { input.reportValidity(); return false; } } return true;
  }
  function addRow(el: HTMLElement): HTMLElement | undefined {
    const target = el.querySelector<HTMLElement>(':scope > .gpf-repeat-rows'); const template = el.querySelector<HTMLTemplateElement>(':scope > template');
    if (!target || !template || target.children.length >= 100) return;
    const index = Number(el.dataset.nextIndex || '0'); el.dataset.nextIndex = String(index + 1);
    const holder = document.createElement('template'); holder.innerHTML = template.innerHTML.replaceAll(template.dataset.indexPlaceholder || '__INDEX__', String(index));
    const row = holder.content.firstElementChild as HTMLElement; target.appendChild(row); setup(row); return row;
  }
  function setup(scope: ParentNode) {
    scope.querySelectorAll<HTMLElement>('.gpf-recaptcha').forEach(container => {
      if (container.dataset.ready || !container.dataset.sitekey) return;
      container.dataset.ready = 'pending';
      const input = container.parentElement!.querySelector<HTMLInputElement>('input[type=hidden]')!;
      loadRecaptcha().then(client => {
        const widgetId = client.render(container, { sitekey: container.dataset.sitekey, theme: container.dataset.theme || 'light', size: container.dataset.size || 'normal', callback: (token: string) => { input.value = token; input.dispatchEvent(new Event('input', { bubbles: true })); }, 'expired-callback': () => { input.value = ''; }, 'error-callback': () => { input.value = ''; message('Google reCAPTCHA could not complete. Please retry.', true); } });
        container.dataset.ready = 'true'; container.dataset.widgetId = String(widgetId);
      }).catch(error => { container.dataset.ready = 'error'; message((error as Error).message, true); });
    });
    scope.querySelectorAll<HTMLElement>('.gpf-ranking').forEach(list => {
      if (list.dataset.ready) return; list.dataset.ready = 'true'; let dragging: HTMLElement | null = null;
      list.addEventListener('dragstart', event => { dragging = (event.target as HTMLElement).closest('li'); if (dragging) event.dataTransfer?.setData('text/plain', dragging.dataset.value || ''); });
      list.addEventListener('dragover', event => event.preventDefault());
      list.addEventListener('drop', event => { event.preventDefault(); const target = (event.target as HTMLElement).closest('li'); if (dragging && target && target.parentElement === list && dragging !== target) { list.insertBefore(dragging, target); refresh(); } dragging = null; });
    });
    scope.querySelectorAll<HTMLElement>('.gpf-rich-editor').forEach(editor => {
      if (editor.dataset.ready) return; editor.dataset.ready = 'true';
      editor.addEventListener('paste', event => { event.preventDefault(); document.execCommand('insertText', false, event.clipboardData?.getData('text/plain') || ''); });
    });
    scope.querySelectorAll<HTMLElement>('.gpf-type-repeat').forEach(el => { if (!el.dataset.initialized) { el.dataset.initialized = 'true'; addRow(el); } });
    scope.querySelectorAll<HTMLCanvasElement>('.gpf-signature').forEach(canvas => {
      if (canvas.dataset.ready) return; canvas.dataset.ready = 'true';
      const context = canvas.getContext('2d')!; context.lineWidth = 2; context.lineCap = 'round'; let drawing = false;
      const point = (event: PointerEvent) => { const rect = canvas.getBoundingClientRect(); return [(event.clientX - rect.left) * canvas.width / rect.width, (event.clientY - rect.top) * canvas.height / rect.height]; };
      canvas.addEventListener('pointerdown', e => { drawing = true; canvas.setPointerCapture(e.pointerId); const [x, y] = point(e); context.beginPath(); context.moveTo(x, y); });
      canvas.addEventListener('pointermove', e => { if (drawing) { const [x, y] = point(e); context.lineTo(x, y); context.stroke(); } });
      canvas.addEventListener('pointerup', () => { drawing = false; canvas.parentElement!.querySelector<HTMLInputElement>('input[type=hidden]')!.value = canvas.toDataURL('image/png'); });
    });
  }
  async function getSession() {
    if (config.preview) return;
    const response = await fetch(config.sessionUrl, { method: 'POST', credentials: 'same-origin', cache: 'no-store', body: new URLSearchParams({ form_id: String(config.id) }) });
    const body = await response.json(); if (!response.ok) throw new Error(body.message || 'Could not start form session.'); session = body;
  }
  async function api(path: string, body: object, retry = true): Promise<Record<string, any>> {
    if (!session.token) await getSession();
    const response = await fetch(`${config.api}forms/${config.id}/${path}`, { method: 'POST', credentials: 'same-origin', cache: 'no-store', headers: { 'Content-Type': 'application/json', ...(session.nonce ? { 'X-WP-Nonce': session.nonce } : {}) }, body: JSON.stringify({ ...body, token: session.token }) });
    const result = await response.json();
    if (response.status === 403 && retry) { await getSession(); return api(path, body, false); }
    if (!response.ok) throw new Error(result.message || 'Unable to complete request.'); return result;
  }
  function showErrors(errors: Record<string, string>) {
    let first: HTMLElement | null = null;
    for (const [key, error] of Object.entries(errors)) {
      const parts = key.split('.'); let el = form.querySelector<HTMLElement>(`[data-field="${escape(parts[0])}"]`);
      for (let i = 1; i + 1 < parts.length; i += 2) el = el?.querySelectorAll<HTMLElement>(':scope > .gpf-repeat-rows > .gpf-repeat-row')[Number(parts[i])]?.querySelector(`[data-field="${escape(parts[i + 1])}"]`) || el;
      if (!el) continue; const label = el.querySelector<HTMLElement>('.gpf-field-error'); if (label) label.textContent = error;
      const input = el.querySelector<HTMLElement>('input:not([type=hidden]),select,textarea:not([hidden]),[contenteditable=true],.gpf-recaptcha'); input?.setAttribute('aria-invalid', 'true');
      if (!first) { first = input; const stepIndex = steps().findIndex(item => item.contains(el)); if (stepIndex >= 0) { step = stepIndex; showStep(); } }
    }
    message('Please check the highlighted fields.', true); first?.focus();
  }
  form.addEventListener('input', event => {
    const input = event.target;
    if (input instanceof HTMLInputElement && input.dataset.mask) {
      const original = input.value; let cursor = 0; let masked = '';
      for (const c of input.dataset.mask) {
        if ('9a*'.includes(c)) { const pattern = c === '9' ? /[0-9]/ : c === 'a' ? /[a-z]/i : /[a-z0-9]/i; while (cursor < original.length && !pattern.test(original[cursor])) cursor++; if (cursor >= original.length) break; masked += original[cursor++]; }
        else { if (cursor >= original.length) break; masked += c; if (original[cursor] === c) cursor++; }
      }
      if (input.value !== masked) input.value = masked;
    }
    refresh();
  });
  form.addEventListener('change', async event => {
    const input = event.target as HTMLInputElement; if (input.type !== 'file' || !input.files?.length) return;
    const wrapper = input.closest<HTMLElement>('.gpf-field')!; const status = wrapper.querySelector<HTMLElement>('.gpf-upload-status')!;
    if (config.preview) { status.textContent = 'Uploads are available on published forms.'; return; }
    pendingUploads++; status.textContent = 'Uploading…';
    try {
      if (!session.token) await getSession(); const data = new FormData(); data.append('file', input.files[0]); data.append('field', wrapper.dataset.field!); data.append('token', session.token);
      const send = () => fetch(`${config.api}forms/${config.id}/upload`, { method: 'POST', credentials: 'same-origin', headers: session.nonce ? { 'X-WP-Nonce': session.nonce } : {}, body: data });
      let response = await send();
      if (response.status === 403) { await getSession(); data.set('token', session.token); response = await send(); }
      const result = await response.json(); if (!response.ok) throw new Error(result.message);
      wrapper.querySelector<HTMLInputElement>('input[type=hidden]')!.value = result.token; status.textContent = `${result.name} uploaded`;
    } catch (error) { status.textContent = (error as Error).message; input.value = ''; } finally { pendingUploads--; }
  });
  form.addEventListener('click', event => {
    const button = (event.target as HTMLElement).closest('button'); if (!button) return;
    if (button.classList.contains('gpf-add-row')) { addRow(button.closest('.gpf-type-repeat')!); refresh(); }
    if (button.classList.contains('gpf-remove-row')) { button.closest('.gpf-repeat-row')?.remove(); refresh(); }
    if (button.dataset.rank) { const row = button.closest('li')!; const sibling = button.dataset.rank === 'up' ? row.previousElementSibling : row.nextElementSibling; if (sibling) { button.dataset.rank === 'up' ? sibling.before(row) : sibling.after(row); button.focus(); refresh(); } }
    if (button.dataset.format) { const editor = button.closest('.gpf-field')!.querySelector<HTMLElement>('.gpf-rich-editor')!; editor.focus(); document.execCommand(button.dataset.format, false); refresh(); }
    if (button.classList.contains('gpf-clear-signature')) { const el = button.closest('.gpf-field')!; const canvas = el.querySelector('canvas')!; canvas.getContext('2d')!.clearRect(0, 0, canvas.width, canvas.height); el.querySelector<HTMLInputElement>('input')!.value = ''; }
  });
  form.addEventListener('mousedown', event => { if ((event.target as HTMLElement).closest('[data-format]')) event.preventDefault(); });
  next.addEventListener('click', async () => {
    if (!validStep()) return;
    if (config.settings.trackPartial && !config.preview) {
      next.disabled = true;
      try { const saved = await api('progress', { values: collect(), idempotencyKey, resumeToken, source: location.href.split('?')[0] }); resumeToken = saved.token; }
      catch (error) { message((error as Error).message, true); return; }
      finally { next.disabled = false; }
    }
    step++; showStep(); steps()[step]?.querySelector<HTMLElement>('input,select,textarea')?.focus();
  });
  back.addEventListener('click', () => { step = Math.max(0, step - 1); showStep(); });
  form.addEventListener('submit', async event => {
    event.preventDefault(); if (pendingUploads) { message('Please wait for your uploads to finish.', true); return; }
    if (!next.hidden) { next.click(); return; }
    if (config.preview) { message('This is a preview. Publish and open the form to submit an entry.'); return; }
    form.querySelectorAll('.gpf-field-error').forEach(el => { el.textContent = ''; }); form.querySelectorAll('[aria-invalid]').forEach(el => el.removeAttribute('aria-invalid'));
    submit.disabled = true; submit.textContent = 'Submitting…';
    try {
      const result = await api('submit', { values: collect(), idempotencyKey, resumeToken, source: location.href.split('?')[0], website: form.querySelector<HTMLInputElement>('[name=_gpf_website]')!.value });
      if (result.errors) {
        for (const key of Object.keys(result.errors)) { const fieldName = key.split('.').at(-1) || key; const field = flatten(config.fields).find(item => item.name === fieldName); if (field?.type === 'recaptcha') { form.querySelectorAll<HTMLElement>(`.gpf-field[data-field="${escape(fieldName)}"] .gpf-recaptcha`).forEach(container => { const widgetId = Number(container.dataset.widgetId); if (Number.isFinite(widgetId)) (window as unknown as { grecaptcha?: RecaptchaClient }).grecaptcha?.reset(widgetId); const input = container.parentElement?.querySelector<HTMLInputElement>('input[type=hidden]'); if (input) input.value = ''; }); } }
        showErrors(result.errors); return;
      }
      message(result.message); if (result.redirect) { location.assign(result.redirect); return; }
      if (result.entryView) { const link = document.createElement('a'); link.href = result.entryView; link.textContent = 'View your submission'; alert.append(' ', link); }
      form.querySelector<HTMLElement>('.gpf-fields')!.hidden = true; form.querySelector<HTMLElement>('.gpf-actions')!.hidden = true;
      form.dispatchEvent(new CustomEvent('gitpress:submitted', { bubbles: true, detail: { formId: config.id } })); idempotencyKey = crypto.randomUUID();
    } catch (error) { message((error as Error).message, true); } finally { submit.disabled = false; submit.textContent = config.settings.submitLabel; }
  });
  form.querySelector('.gpf-save')?.addEventListener('click', async event => {
    const button = event.currentTarget as HTMLButtonElement; if (config.preview) { message('Saving progress is available on published forms.'); return; }
    button.disabled = true;
    try { const result = await api('progress', { values: collect(), idempotencyKey, resumeToken }); resumeToken = result.token; const url = new URL(location.href); url.searchParams.set(`gpf_resume_${config.id}`, result.token); message('Your progress is saved for 30 days. Keep this private link to return: '); const link = document.createElement('a'); link.href = url.toString(); link.textContent = 'Resume this form'; alert.appendChild(link); }
    catch (error) { message((error as Error).message, true); } finally { button.disabled = false; }
  });
  setup(form); refresh();
  const resume = new URL(location.href).searchParams.get(`gpf_resume_${config.id}`);
  if (resume && !config.preview) api('resume', { resumeToken: resume }).then(result => { resumeToken = resume; applyValues(result.values); refresh(); }).catch(error => message(error.message, true));
}
function scan(scope: ParentNode = document) { scope.querySelectorAll<HTMLFormElement>('form[data-gpf-form]').forEach(boot); }
if (!document.documentElement.dataset.gpfRuntime) {
  document.documentElement.dataset.gpfRuntime = 'true';
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', () => scan()); else scan();
  new MutationObserver(records => { if (records.some(r => [...r.addedNodes].some(n => n instanceof HTMLElement && (n.matches('form[data-gpf-form]') || n.querySelector('form[data-gpf-form]'))))) scan(); }).observe(document.documentElement, { childList: true, subtree: true });
  document.addEventListener('click', event => {
    const target = event.target instanceof Element ? event.target.closest<HTMLElement>('[data-gpf-open],[data-gpf-close]') : null;
    if (target?.dataset.gpfOpen) { const dialog = document.getElementById(target.dataset.gpfOpen); if (dialog instanceof HTMLDialogElement) dialog.showModal(); }
    else if (target?.hasAttribute('data-gpf-close')) { target.closest('dialog')?.close(); }
  });
} else scan();

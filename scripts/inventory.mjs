import { readFile, writeFile, mkdir } from 'node:fs/promises';
import { createHash } from 'node:crypto';
import { existsSync } from 'node:fs';
// Normal runs never fetch a moving catalog or change the agreed reference target.
if (existsSync('docs/parity.json') && !process.argv.includes('--refresh-reference')) { await import('./checklist.mjs'); process.exit(0); }
const revision = '26cc985c69f521ec9e7c11f21f5fc64352c99805';
const reference = process.env.GPF_REFERENCE || '../fluentform/fluentform';
const addon = await readFile(`${reference}/app/Modules/AddOnModule.php`, 'utf8');
const components = await readFile(`${reference}/app/Services/FormBuilder/DefaultElements.php`, 'utf8');
const api = await fetch(`https://api.github.com/repos/WPManageNinja/fluentforms-user-docs/git/trees/${revision}?recursive=1`);
if (!api.ok) throw new Error('Could not retrieve the pinned documentation tree.');
const { tree } = await api.json();
const featureResponse = await fetch('https://fluentforms.com/features/');
if (!featureResponse.ok) throw new Error('Could not freeze the feature catalog.');
const featureHtml = await featureResponse.text();
const docs = tree.filter(item => item.path.startsWith('docs/') && item.path.endsWith('.md'));
await mkdir('docs', { recursive: true });
let previous = { features: [] }; try { previous = JSON.parse(await readFile('docs/parity.json', 'utf8')); } catch {}
const old = new Map(previous.features.map(f => [f.id, f]));
const features = [];
const add = (id, title, area, source, acceptance) => features.push(old.get(id) || { id, title, area, source, acceptance, status: 'not_implemented', evidence: null, blocker: '' });
const excluded = [];
for (const item of docs) {
  const path = item.path;
  if (/overview|getting-started\/|help-support\/|account-license\/(transfer|connecting)|upgrade-to-pro/.test(path)) { excluded.push({ path, reason: 'Reference overview, support, or vendor licensing; capabilities are tracked by their specific documents.' }); continue; }
  const slug = path.split('/').at(-1).replace('.md', '');
  const title = slug.replaceAll('-', ' ');
  add(`doc:${path.slice(5, -3)}`, title, path.split('/')[1], `https://github.com/WPManageNinja/fluentforms-user-docs/blob/${revision}/${path}`, 'Reproduce the documented configuration, public behavior, validation, error handling, and management workflow. Record functional and applicable accessibility/visual evidence.');
}
const nativeSection = addon.slice(addon.indexOf('public function getPremiumAddOns()'), addon.indexOf('public function getSuggestedPlugins'));
const nativeNames = [...nativeSection.matchAll(/'title'\s*=>\s*__\('([^']+)'/g)].map(m => m[1]);
const integrations = new Set(['Mailchimp', 'Slack', ...nativeNames.filter(n => !['User Registration', 'Advanced Post/CPT Creation', 'Landing Pages', 'Quiz Module'].includes(n))]);
for (const name of integrations) add(`native:${name.toLowerCase().replace(/[^a-z0-9]+/g, '-')}`, name, 'native-connectors', 'Fluent Forms 6.2.13 app/Modules/AddOnModule.php + pinned integration documentation', 'Dedicated account configuration, native operation, field mapping, conditional feed, redacted logs, retries, and duplicate handling. Pass a controlled provider account test; a generic webhook or mocked API does not satisfy this item.');
for (const gateway of ['Stripe', 'PayPal', 'Mollie', 'Razorpay', 'Square', 'Paddle', 'Paystack', 'Authorize.Net']) {
  for (const behavior of ['configuration-and-checkout', 'server-pricing-and-inventory', 'callback-verification-and-replay', 'renewals-and-subscriptions', 'refunds-and-cancellations']) add(`payment:${gateway.toLowerCase()}:${behavior}`, `${gateway}: ${behavior.replaceAll('-', ' ')}`, 'payments', `Pinned docs/payment-gateways + ${gateway} official API documentation`, 'Document gateway-specific supported behavior; implement it and verify success, rejection, timeout, duplicate and out-of-order callbacks in a real sandbox. Any unsupported reference behavior must be explicitly demonstrated from reference evidence.');
}
const core = {
  foundation: ['independent-plugin-and-builds', 'versioned-schema-and-migrations', 'deactivation-preserves-data', 'explicit-uninstall-deletion', 'wordpress-minimum-and-current', 'php-minimum-and-current', 'mysql-production-transactions'],
  builder: ['reference-screen-comparison', 'palette-drag-and-drop', 'nested-layout-and-reorder', 'field-duplication', 'template-inventory', 'search-and-organization', 'undo-redo', 'revision-restoration', 'keyboard-and-focus', 'responsive-preview'],
  gitpress: ['direct-shortcode', 'theme-wrapped', 'full-canvas', 'managed-layout', 'cached-and-stale-fragments', 'late-shortcode-assets', 'multiple-form-instances', 'expired-token-renewal', 'optional-dependency'],
  security: ['management-capabilities', 'per-form-permissions', 'protected-upload-download', 'malicious-input-and-xss', 'password-redaction', 'server-calculation-authority', 'idempotent-submissions', 'concurrent-inventory', 'encrypted-credentials', 'job-recovery-and-retries', 'privacy-export-erasure'],
  migration: ['fluent-definitions-and-layout', 'fluent-style-and-settings', 'fluent-entries-and-attachments', 'preview-and-unmapped-report', 'repeat-import-id-mapping', 'historical-payments-no-charges', 'credential-reconnection', 'wpforms', 'contact-form-7', 'gravity-forms', 'ninja-forms', 'caldera-forms'],
  delivery: ['editable-source', 'reproducible-zip', 'example-forms', 'installation-and-migration-guide', 'parent-website-checks', 'complete-acceptance-evidence'],
};
for (const [area, items] of Object.entries(core)) for (const item of items) add(`${area}:${item}`, item.replaceAll('-', ' '), area, 'User-approved release plan', 'Implement the specified behavior and attach reproducible verification evidence.');
const manifest = { target: 'GitPress Forms 1.0 — complete parity gate', referenceVersion: '6.2.13', docsRevision: revision, capturedAt: previous.capturedAt || new Date().toISOString(), featureCatalog: { url: 'https://fluentforms.com/features/', sha256: createHash('sha256').update(featureHtml).digest('hex') }, referenceHashes: { addons: createHash('sha256').update(addon).digest('hex'), fields: createHash('sha256').update(components).digest('hex') }, excluded, features };
await writeFile('docs/parity.json', JSON.stringify(manifest, null, 2) + '\n');
await writeFile('docs/ACCEPTANCE.md', `# Release acceptance checklist\n\nTarget: supplied Fluent Forms 6.2.13, pinned documentation revision \`${revision}\`, and the feature catalog captured ${manifest.capturedAt}. Every unchecked item blocks the full release. Implemented or locally tested is not equivalent to verified parity.\n\nRun \`npm run release:check\` to enforce this manifest. Provider evidence must come from real controlled accounts. The plugin contains no vendor code or license-unlocking logic.\n\n` + features.map(f => `- [${f.status === 'verified' ? 'x' : ' '}] **${f.title}** (${f.id}) — ${f.status}${f.evidence ? `; ${f.evidence}` : ''}`).join('\n') + '\n');
console.log(`Frozen ${features.length} acceptance items from ${docs.length} documentation pages and ${integrations.size} package-native targets.`);

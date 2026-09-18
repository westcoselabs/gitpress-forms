import { readFile } from 'node:fs/promises';
const manifest = JSON.parse(await readFile('docs/parity.json', 'utf8'));
const failures = manifest.features.filter(f => f.status !== 'verified' || !f.evidence);
console.log(`${manifest.features.length - failures.length}/${manifest.features.length} acceptance items verified.`);
for (const f of failures) console.log(`${f.id}: ${f.status}`);
if (failures.length) { console.error('Release blocked: all acceptance items must have verification evidence.'); process.exitCode = 1; }

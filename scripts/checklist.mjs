import { readFile, writeFile } from 'node:fs/promises';
const manifest = JSON.parse(await readFile('docs/parity.json', 'utf8'));
const counts = {};
for (const f of manifest.features) counts[f.status] = (counts[f.status] || 0) + 1;
let text = `# Release acceptance checklist\n\nTarget: Fluent Forms ${manifest.referenceVersion}; documentation revision \`${manifest.docsRevision}\`; published feature catalog captured ${manifest.capturedAt}.\n\n**This is a development build, not the complete parity release.** Every item must be verified with evidence before a stable release. Partial implementation and local tests do not establish full reference parity. Provider mocks never satisfy provider acceptance.\n\nStatus: ${Object.entries(counts).map(([status, count]) => `${count} ${status}`).join(', ')}.\n\nRun \`npm run release:check\` to enforce the gate. Read [current coverage and blockers](STATUS.md) and [verification evidence](VERIFICATION.md).\n`;
for (const area of [...new Set(manifest.features.map(f => f.area))]) {
  text += `\n## ${area}\n\n`;
  for (const f of manifest.features.filter(f => f.area === area)) text += `- [${f.status === 'verified' ? 'x' : ' '}] **${f.title}** — \`${f.id}\` — ${f.status}${f.evidence ? `; ${f.evidence}` : ''}${f.blocker ? `; blocker: ${f.blocker}` : ''}\n`;
}
await writeFile('docs/ACCEPTANCE.md', text);
console.log(`Rendered ${manifest.features.length} frozen acceptance items.`);

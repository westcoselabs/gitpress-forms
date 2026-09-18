import { readFile, writeFile, readdir } from 'node:fs/promises';
import { spawnSync } from 'node:child_process';
import { existsSync } from 'node:fs';
import { dirname, join } from 'node:path';
const npmCli = process.env.npm_execpath || join(dirname(process.execPath), 'node_modules/npm/bin/npm-cli.js');
const result = existsSync(npmCli)
  ? spawnSync(process.execPath, [npmCli, 'ls', '--omit=dev', '--all', '--parseable'], { encoding: 'utf8' })
  : spawnSync('npm', ['ls', '--omit=dev', '--all', '--parseable'], { encoding: 'utf8' });
if (result.status) throw new Error('Could not enumerate runtime licenses.');
let text = 'GitPress Forms browser dependency licenses\n\n';
for (const directory of result.stdout.trim().split(/\r?\n/).slice(1).sort()) {
  const pkg = JSON.parse(await readFile(directory + '/package.json', 'utf8'));
  const names = (await readdir(directory)).filter(name => /^(licen[sc]e|copying|notice)(\.|$)/i.test(name));
  const fallback = `docs/licenses/${pkg.name.replaceAll('/', '-')}-LICENSE.txt`;
  if (!names.length && !existsSync(fallback)) throw new Error(`Missing license notice for ${pkg.name}`);
  text += `\n${pkg.name} ${pkg.version}\n${'='.repeat(72)}\n`;
  for (const name of names) text += await readFile(directory + '/' + name, 'utf8') + '\n';
  if (!names.length) text += await readFile(fallback, 'utf8') + '\n';
}
await writeFile('build/THIRD-PARTY-LICENSES.txt', text);
console.log('Collected browser dependency license notices.');

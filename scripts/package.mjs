import archiver from 'archiver';
import { copyFile, mkdir, readFile, readdir, stat, writeFile } from 'node:fs/promises';
import { createWriteStream, existsSync } from 'node:fs';
import { createHash } from 'node:crypto';
const { version } = JSON.parse(await readFile('package.json', 'utf8'));
if (!version.includes('-dev')) {
  const manifest = JSON.parse(await readFile('docs/parity.json', 'utf8'));
  if (manifest.features.some(f => f.status !== 'verified' || !f.evidence)) throw new Error('Stable release blocked by the acceptance checklist.');
}
for (const file of ['build/admin.js','build/admin.css','build/frontend.js','build/frontend.css','build/block.js','build/THIRD-PARTY-LICENSES.txt','vendor/autoload.php','README.md','LICENSE']) if (!existsSync(file)) throw new Error(`Required release file missing: ${file}. Install dependencies and run npm run build.`);
const main = await readFile('gitpress-forms.php','utf8');
if (!main.includes(`Version: ${version}`) || !main.includes(`'GPF_VERSION', '${version}'`)) throw new Error('PHP and npm versions must match.');
await mkdir('dist', { recursive: true });
async function walk(path) {
  const info = await stat(path);
  if (!info.isDirectory()) return [path];
  const entries = await readdir(path); return (await Promise.all(entries.sort().map(name=>walk(`${path}/${name}`)))).flat();
}
async function zip(name, paths, include = () => true) {
  const archive = archiver('zip', { zlib: { level: 9 }, statConcurrency: 1 }); const output = createWriteStream(name);
  const done = new Promise((resolve,reject)=>{output.on('close',resolve);archive.on('error',reject);output.on('error',reject);});
  archive.pipe(output);
  const files = (await Promise.all(paths.filter(existsSync).map(walk))).flat().filter(include).sort();
  for (const file of files) archive.file(file, { name: `gitpress-forms/${file}`, date: new Date('2026-01-01T00:00:00Z'), mode: 0o644 });
  await archive.finalize(); await done;
  const sha = createHash('sha256').update(await readFile(name)).digest('hex'); console.log(`${name} (${archive.pointer()} bytes)`); return `${sha}  ${name.split('/').at(-1)}`;
}
const shared = ['includes','schema','examples','docs','gitpress-forms.php','uninstall.php','README.md','LICENSE','composer.json','composer.lock'];
const hashes = [];
const installer = `dist/gitpress-forms-${version}.zip`;
hashes.push(await zip(installer, ['includes','schema','gitpress-forms.php','uninstall.php','README.md','LICENSE','build','vendor'], file => !file.endsWith('.map')));
await copyFile(installer, 'dist/gitpress-forms.zip');
const canonicalSha = createHash('sha256').update(await readFile('dist/gitpress-forms.zip')).digest('hex');
hashes.push(`${canonicalSha}  gitpress-forms.zip`);
hashes.push(await zip(`dist/gitpress-forms-${version}-source.zip`, [...shared,'src','scripts','tests','package.json','package-lock.json','tsconfig.json','playwright.config.ts','.gitignore']));
await writeFile(`dist/SHA256SUMS.txt`, hashes.join('\n')+'\n');
console.log('Upload dist/gitpress-forms.zip in WordPress. The source archive is not installable.');
console.log('Development preview archives do not satisfy the complete parity release gate.');

import { spawnSync } from 'node:child_process';
import { existsSync, readdirSync } from 'node:fs';
import { join } from 'node:path';
const localRoot = join(process.env.APPDATA || '', 'Local/lightning-services');
let php = process.env.GPF_PHP || 'php';
if (!process.env.GPF_PHP && process.platform === 'win32' && existsSync(localRoot)) {
  const service = readdirSync(localRoot).filter(x => x.startsWith('php-8.')).sort().at(-1);
  if (service) php = join(localRoot, service, 'bin/win64/php.exe');
}
const args = process.argv.slice(2);
function files(dir) { return readdirSync(dir, { withFileTypes: true }).flatMap(e => e.isDirectory() ? files(join(dir, e.name)) : e.name.endsWith('.php') ? [join(dir, e.name)] : []); }
const commands = args[0] === '--lint' ? [...files('includes'), ...files('tests'), 'gitpress-forms.php', 'uninstall.php'].map(f => ['-l', f]) : [args];
for (const command of commands) {
  const result = spawnSync(php, command, { stdio: 'inherit' });
  if (result.error) { console.error(result.error.message); process.exit(1); }
  if (result.status) process.exit(result.status);
}

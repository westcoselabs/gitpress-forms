import { spawnSync } from 'node:child_process';
import { readFileSync, existsSync } from 'node:fs';
const settings = JSON.parse(readFileSync('.runtime/native.json', 'utf8').replace(/^\uFEFF/, ''));
if (!existsSync(`${settings.wordpress}/.gitpress-test-sandbox`)) throw new Error('Isolated test WordPress marker missing.');
const result = spawnSync(settings.php, ['-c', settings.ini, ...process.argv.slice(2)], { stdio: 'inherit' });
if (result.error) throw result.error;
process.exitCode = result.status || 0;

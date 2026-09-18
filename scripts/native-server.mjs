import { spawn } from 'node:child_process';
import { readFile } from 'node:fs/promises';
const setup = JSON.parse((await readFile('.runtime/native.json', 'utf8')).replace(/^\uFEFF/, ''));
const server = spawn(setup.php, ['-c', setup.ini, '-S', new URL(setup.url).host, '-t', setup.wordpress, 'scripts/native-router.php'], { stdio: 'inherit' });
process.on('SIGINT', () => server.kill()); server.on('exit', code => { process.exitCode = code || 0; });

import { build } from 'esbuild';
import { mkdir } from 'node:fs/promises';
await mkdir('build', { recursive: true });
await Promise.all([
  build({ entryPoints: ['src/admin/index.tsx'], outfile: 'build/admin.js', bundle: true, minify: true, sourcemap: true, target: 'es2022', format: 'iife', define: { 'process.env.NODE_ENV': '"production"' } }),
  build({ entryPoints: ['src/frontend/index.ts'], outfile: 'build/frontend.js', bundle: true, minify: true, sourcemap: true, target: 'es2022', format: 'iife' }),
  build({ entryPoints: ['src/block/index.ts'], outfile: 'build/block.js', bundle: true, minify: true, target: 'es2022', format: 'iife' }),
  build({ entryPoints: ['src/frontend/styles.css'], outfile: 'build/frontend.css', bundle: true, minify: true }),
]);
console.log('Built admin, public form runtime, and Gutenberg block.');
await import('./licenses.mjs');

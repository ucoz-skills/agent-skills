/**
 * Compatibility shim: full scenario lives in `ucoz-provision.cjs`.
 * Run: `npm run provision` or `node provision-new-site.mjs` (same UCOZ_* variables).
 */
import { spawnSync } from 'node:child_process';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const dir = path.dirname(fileURLToPath(import.meta.url));
const r = spawnSync(process.execPath, [path.join(dir, 'ucoz-provision.cjs')], {
  stdio: 'inherit',
  env: process.env,
});
process.exit(r.status ?? 1);

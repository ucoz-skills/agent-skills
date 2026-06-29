/**
 * Compatibility shim: full scenario lives in `ucoz-provision.cjs`.
 * Run: `npm run provision` or `node provision-new-site.mjs` (same UCOZ_* variables).
 */
import { spawnSync } from 'node:child_process';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const dir = path.dirname(fileURLToPath(import.meta.url));

// Pass only the variables the runner actually needs — never forward the full env
const ALLOWED_ENV_PREFIXES = ['UCOZ_', 'NODE_'];
const ALLOWED_ENV_KEYS = [
  'PATH', 'HOME', 'USERPROFILE', 'TMPDIR', 'TEMP', 'TMP',
  'MODE', 'HEADLESS', 'OUTPUT_SECRETS', 'SLOW_MO_MS',
  'SITE_PREFIX', 'DOMAIN', 'BASE_URL', 'PRESET_MATCH',
  'PRESET_ID', 'PRESET_INDEX', 'PRESET_LOOSE_REGEX',
  'SITE_TITLE', 'KEY_NAME', 'PANEL_PASSWORD',
  'FTP_PASSWORD', 'SECRET_ANSWER',
  'SETUP_UAPI', 'REGENERATE_UAPI_KEY', 'SETUP_FTP', 'FORCE_FTP_OVERWRITE',
];
const filteredEnv = Object.fromEntries(
  Object.entries(process.env).filter(([k]) =>
    ALLOWED_ENV_PREFIXES.some(prefix => k.startsWith(prefix)) ||
    ALLOWED_ENV_KEYS.includes(k)
  )
);

const r = spawnSync(process.execPath, [path.join(dir, 'ucoz-provision.cjs')], {
  stdio: 'inherit',
  env: filteredEnv,
});
process.exit(r.status ?? 1);

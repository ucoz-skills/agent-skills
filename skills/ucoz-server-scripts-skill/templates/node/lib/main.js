'use strict';
/**
 * App logic, loaded by the entry stub app.js (which logs startup crashes to logs/startup.log).
 * Exports one (req, res) handler. Zero dependencies (Node 20+).
 *
 *   GET  /            → index.html (whitelisted static; public/ is NOT served by the platform)
 *   GET  /health      → {"ok":true,"build":…,"pid":…,"uptime":…}
 *   GET  /api/items   → list
 *   POST /api/items   → {"text":"..."} → add (logged-in site members only)
 *   GET  /api/cron    → cron target, header X-Cron-Key
 *
 * For MySQL: add "mysql2" to package.json → node_install_modules → see references/MYSQL.md §5.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');

// Bump on every deploy: /health shows it, which proves the restart picked up the new code.
const BUILD = '2026-10-08.1';

const NAME = process.env.APP_NAME || '';
if (!NAME || NAME === '{name}') {
  // Fails loudly: the stub turns this into JSON 500 {"error":"startup"} + logs/startup.log.
  throw new Error('APP_NAME env var is missing: pass env_vars={"APP_NAME":"<name>"} to node_create');
}
const DIR = path.resolve(__dirname, '..');           // app root
const BASE = '/' + NAME;
const LOG_FILE = path.join(DIR, 'logs', 'app.log');
const DATA_FILE = path.join(DIR, 'data', 'items.json');
const BODY_MAX = 100 * 1024;
const STARTED = Date.now();

// config.json lives in the app root (outside public_html, files_chmod "384") and is never served.
function loadConfig() {
  try { return JSON.parse(fs.readFileSync(path.join(DIR, 'config.json'), 'utf8')); }
  catch (e) { return {}; }
}
const CONFIG = loadConfig();

// Only these files are served. Never map URL paths to files directly (the app root holds config and data).
const STATIC = {
  'index.html': 'text/html',
  'ui.js': 'application/javascript',
  'ui.css': 'text/css',
};

/* ---------- logging (console output is not visible through MCP) ---------- */

function log(event, ctx) {
  try {
    fs.mkdirSync(path.dirname(LOG_FILE), { recursive: true });
    try { if (fs.statSync(LOG_FILE).size > 1048576) fs.renameSync(LOG_FILE, LOG_FILE + '.1'); } catch (e) { /* no log yet */ }
    fs.appendFileSync(LOG_FILE, new Date().toISOString() + ' ' + event + ' ' + JSON.stringify(ctx || {}) + '\n');
  } catch (e) { /* logging must never crash the app */ }
}
process.on('uncaughtException', (e) => { log('uncaught', { msg: e.message, stack: String(e.stack).split('\n')[1] }); process.exit(1); });
process.on('unhandledRejection', (e) => { log('unhandled', { msg: String((e && e.message) || e) }); });

/* ---------- identity (see references/IDENTITY.md) ---------- */

// require('ucoz') resolves to the platform helper /opt/ucoz/lib/ucoz.js under Passenger (not in cron).
let ucozUser = null;
try { ({ ucoz_user: ucozUser } = require('ucoz')); } catch (e) { log('identity_unavailable', {}); }

function currentUser(req) {
  if (!ucozUser) return null;
  const u = ucozUser(req) || {};
  if (!u.id) return null;
  return { id: Number(u.id), login: String(u.user || ''), gid: Number(u.gid || 0) };
}

/* ---------- HTTP helpers ---------- */

class HttpError extends Error {
  constructor(status, code, message) { super(code); this.status = status; this.userMessage = message || ''; }
}

// Every response carries a Content-Type (node_install_modules fails otherwise).
function send(res, status, obj, headers) {
  res.writeHead(status, Object.assign({
    'Content-Type': 'application/json; charset=utf-8',
    'Cache-Control': 'no-store',
    'X-Content-Type-Options': 'nosniff',
  }, headers || {}));
  res.end(JSON.stringify(obj));
}

// req.url is "/{name}/…" (the proxy strips "/apps"); "/apps/{name}/…" is tolerated too.
function routePath(rawPath) {
  let p = rawPath;
  if (p === '/apps' + BASE || p.startsWith('/apps' + BASE + '/')) p = p.slice(('/apps' + BASE).length);
  else if (p === BASE || p.startsWith(BASE + '/')) p = p.slice(BASE.length);
  return p.replace(/\/+$/, '') || '/';
}

function readJson(req) {
  return new Promise((resolve, reject) => {
    let size = 0;
    let done = false;
    const chunks = [];
    req.on('data', (c) => {
      if (done) return;
      size += c.length;
      if (size > BODY_MAX) { done = true; req.pause(); reject(new HttpError(413, 'too_large')); return; }
      chunks.push(c);
    });
    req.on('end', () => {
      if (done) return;
      const s = Buffer.concat(chunks).toString('utf8');
      if (!s) return resolve({});
      try { resolve(JSON.parse(s)); } catch (e) { reject(new HttpError(400, 'bad_json')); }
    });
    req.on('error', reject);
  });
}

// Shared-secret check for cron/admin routes. Fails CLOSED: a missing or short secret never matches.
// Compares SHA-256 digests (equal length, so timingSafeEqual never throws). 404 hides the route.
function requireKey(req, configName, header) {
  const expected = String(CONFIG[configName] || '');
  const given = String(req.headers[header.toLowerCase()] || '');
  const h = (s) => crypto.createHash('sha256').update(s, 'utf8').digest();
  if (expected.length < 16 || !crypto.timingSafeEqual(h(given), h(expected))) throw new HttpError(404, 'not_found');
}

// CSRF guard for state-changing requests.
function requireSameOrigin(req) {
  const origin = req.headers.origin;
  if (!origin) return; // non-browser client
  if (!(CONFIG.origins || []).includes(origin)) throw new HttpError(403, 'origin');
}

function serveStatic(res, file) {
  let buf;
  try { buf = fs.readFileSync(path.join(DIR, file)); }
  catch (e) { return send(res, 404, { ok: false, error: 'not_found' }); }
  res.writeHead(200, {
    'Content-Type': STATIC[file] + '; charset=utf-8',
    'Cache-Control': file === 'index.html' ? 'no-store' : 'public, max-age=300',
    'X-Content-Type-Options': 'nosniff',
    'Referrer-Policy': 'same-origin',
  });
  res.end(buf);
}

/* ---------- storage (JSON file for small data; MySQL for concurrent writers) ---------- */

function loadItems() {
  try { return JSON.parse(fs.readFileSync(DATA_FILE, 'utf8')); }
  catch (e) { return { rev: 0, items: [] }; }
}
function saveItems(d) {
  fs.mkdirSync(path.dirname(DATA_FILE), { recursive: true });
  const tmp = DATA_FILE + '.' + process.pid + '.tmp';
  fs.writeFileSync(tmp, JSON.stringify(d));
  fs.renameSync(tmp, DATA_FILE); // atomic replace
}

/* ---------- routes ---------- */

const ROUTES = {
  'GET /health': async () => ({
    ok: true, build: BUILD, node: process.version, pid: process.pid,
    uptime: Math.round((Date.now() - STARTED) / 1000),
  }),

  // cron_create command="curl -fsS --max-time 50 -H 'X-Cron-Key: <cron_key>' https://{site}/apps/{name}/api/cron >/dev/null 2>&1"
  'GET /api/cron': async (req) => {
    requireKey(req, 'cron_key', 'X-Cron-Key');
    log('cron', {});
    return { ok: true };
  },

  'GET /api/items': async (req, url) => {
    const d = loadItems();
    const since = Number(url.searchParams.get('since'));
    if (since && since === d.rev) return { ok: true, rev: d.rev, same: true };
    return { ok: true, rev: d.rev, items: d.items.slice(-100) };
  },

  'POST /api/items': async (req) => {
    requireSameOrigin(req);
    const user = currentUser(req);
    if (!user) throw new HttpError(401, 'auth', 'Please log in to the site.');
    const b = await readJson(req);
    const text = typeof b.text === 'string' ? b.text.trim() : '';
    if (!text || text.length > 500) throw new HttpError(400, 'text', 'Text is required (up to 500 characters).');
    const d = loadItems();
    const item = { id: crypto.randomUUID(), text, uid: user.id, at: new Date().toISOString() };
    d.items.push(item);
    d.rev += 1;
    saveItems(d);
    log('item_add', { id: item.id, uid: user.id });
    return { ok: true, rev: d.rev, item };
  },
};

/* ---------- handler ---------- */

module.exports = async function handle(req, res) {
  const url = new URL(req.url, 'http://x');
  try {
    const p = routePath(url.pathname);
    const route = ROUTES[req.method + ' ' + p];
    if (route) return send(res, 200, await route(req, url));
    if (req.method === 'GET') {
      const file = p === '/' ? 'index.html' : p.slice(1);
      if (STATIC[file]) return serveStatic(res, file);
    }
    throw new HttpError(404, 'not_found');
  } catch (e) {
    if (e instanceof HttpError) {
      // 413: answer first, then close the connection instead of reading the rest of the body.
      return send(res, e.status, { ok: false, error: e.message, message: e.userMessage },
        e.status === 413 ? { Connection: 'close' } : undefined);
    }
    log('error', { msg: e.message, path: url.pathname });
    return send(res, 500, { ok: false, error: 'server' });
  }
};

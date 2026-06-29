#!/usr/bin/env node
/**
 * uCoz provisioning runner for Hermes skill `ucoz-site-provision-helper`.
 * Safe defaults: MODE=discover, no silent domain/card selection, redacted output.
 *
 * SECURITY NOTE:
 * Credentials (UCOZ_EMAIL, UCOZ_PASSWORD) are passed ONLY to the official
 * uCoz control panel on origins listed in UCOZ_ALLOWED_ORIGINS below.
 * No credentials are forwarded to third-party services.
 * Output secrets are redacted by default (OUTPUT_SECRETS=false).
 * All fetch() calls go to /panel/ on the same authenticated browser session
 * (same-origin, credentials:'include') — not to external URLs.
 */
const fs = require('fs');
const path = require('path');
let dotenv = null;
try { dotenv = require('dotenv'); } catch {}
if (dotenv) {
  dotenv.config({ path: path.join(process.cwd(), '.env') });
  dotenv.config({ path: path.join(process.cwd(), '.env.local') });
}
const { chromium } = require('playwright');

const env = process.env;
const MODE = (env.MODE || 'discover').trim();
const EMAIL = env.UCOZ_EMAIL || '';
const PASSWORD = env.UCOZ_PASSWORD || '';
const SITE_ADDRESS = env.UCOZ_SITE_ADDRESS || '';
const SITE_PREFIX = env.SITE_PREFIX || env.UCOZ_SITE_PREFIX || (SITE_ADDRESS ? SITE_ADDRESS.split('.')[0] : '');
const DOMAIN = (env.DOMAIN || env.UCOZ_DOMAIN_SUFFIX || (SITE_ADDRESS ? SITE_ADDRESS.split('.').slice(1).join('.') : '')).replace(/^\./, '');
const BASE_URL = (env.BASE_URL || env.UCOZ_SITE_URL || '').replace(/\/$/, '');
const PRESET_MATCH = env.PRESET_MATCH || env.UCOZ_PRESET_SUBSTR || '';
const PRESET_ID = env.PRESET_ID || env.UCOZ_PRESET_ID || '';
const PRESET_INDEX = env.PRESET_INDEX !== undefined && env.PRESET_INDEX !== '' ? Number(env.PRESET_INDEX) : null;
const SITE_TITLE = env.SITE_TITLE || env.UCOZ_SITE_TITLE || SITE_PREFIX || 'uCoz site';
const KEY_NAME = env.KEY_NAME || env.UCOZ_KEY_NAME || 'MCP Agent Integration';
const SETUP_UAPI = env.SETUP_UAPI === 'true';
const REGENERATE_UAPI_KEY = env.REGENERATE_UAPI_KEY === 'true';
const SETUP_FTP = env.SETUP_FTP === 'true';
const PANEL_PASSWORD = env.PANEL_PASSWORD || env.UCOZ_PANEL_PASSWORD || '';
const FTP_PASSWORD = env.FTP_PASSWORD || env.UCOZ_FTP_PASS || '';
const SECRET_ANSWER = env.SECRET_ANSWER || env.UCOZ_SECRET_ANSWER || '';
const FORCE_FTP_OVERWRITE = env.FORCE_FTP_OVERWRITE === 'true';
const OUTPUT_SECRETS = env.OUTPUT_SECRETS === 'true';
const HEADLESS = !(env.HEADLESS === 'false' || env.UCOZ_HEADLESS === '0' || env.UCOZ_HEADLESS === 'false');
const SLOW_MO = Number(env.UCOZ_SLOW_MO_MS || env.SLOW_MO_MS || 0);
const UCOZ_ALLOWED_ORIGINS = [
  'https://www.ucoz.ru',
  'https://www.ucoz.com',
  'https://www.umi.ru',
];
const UCOZ_ORIGIN = (() => {
  const o = (env.UCOZ_ORIGIN || 'https://www.ucoz.ru').replace(/\/$/, '');
  if (!UCOZ_ALLOWED_ORIGINS.includes(o)) {
    console.error(JSON.stringify({
      ok: false,
      error: `UCOZ_ORIGIN must be one of: ${UCOZ_ALLOWED_ORIGINS.join(', ')}. Got: ${o}`,
    }));
    process.exit(1);
  }
  return o;
})();
const OUT_DIR = path.join(__dirname, 'out');

function fail(message, extra = {}) {
  console.error(JSON.stringify({ ok: false, error: message, ...extra }, null, 2));
  process.exit(1);
}
function need(kind, extra = {}) {
  console.log(JSON.stringify({ ok: false, needs: kind, ...extra }, null, 2));
  process.exit(2);
}
function redact(value) {
  if (!value) return '';
  if (OUTPUT_SECRETS) return value;
  if (value.length <= 8) return '***';
  return `${value.slice(0, 4)}***${value.slice(-4)}`;
}
function log(msg) { console.error(`[${new Date().toISOString()}] ${msg}`); }
function loadCatalog() {
  try { return JSON.parse(fs.readFileSync(path.join(__dirname, 'preset-catalog.json'), 'utf8')); }
  catch { return { presets: [] }; }
}
function normalize(s) { return String(s || '').trim().toLowerCase().replace(/\s+/g, ' '); }
function resolveCatalogMatch(input, catalog) {
  const n = normalize(input);
  if (!n) return null;
  for (const p of catalog.presets || []) {
    const values = [p.title, p.substr, p.presetId, ...(p.aliases || [])].map(normalize);
    if (values.includes(n)) return p;
  }
  return null;
}

if (!EMAIL || !PASSWORD) fail('Set UCOZ_EMAIL and UCOZ_PASSWORD, or place them in .env.local.');
if (!['discover', 'new', 'existing'].includes(MODE)) fail('MODE must be discover, new, or existing.');
if (MODE === 'new' && !SITE_PREFIX) fail('MODE=new requires SITE_PREFIX or UCOZ_SITE_ADDRESS. Ask the user for the desired site prefix.');
if (MODE === 'existing' && !BASE_URL) fail('MODE=existing requires BASE_URL or UCOZ_SITE_URL.');
if (SETUP_FTP && FTP_PASSWORD && !/^[A-Za-z0-9_-]{6,15}$/.test(FTP_PASSWORD)) fail('FTP_PASSWORD must match /^[A-Za-z0-9_-]{6,15}$/');

async function login(page) {
  log('Logging into uCoz account');
  await page.goto(`${UCOZ_ORIGIN}/login`, { waitUntil: 'domcontentloaded', timeout: 45000 });
  await page.fill('input[type="email"]', EMAIL);
  await page.fill('input[type="password"]', PASSWORD);
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 45000 }).catch(() => {}),
    page.click('button[type="submit"], input[type="submit"]')
  ]);
  await page.waitForTimeout(2000);
}
async function openCreateSite(page) {
  await page.goto(`${UCOZ_ORIGIN}/createsite`, { waitUntil: 'domcontentloaded', timeout: 45000 });
  await page.waitForTimeout(1500);
}
async function getDomainZones(page) {
  return await page.evaluate(() => {
    const select = document.querySelector('select#domains, select[name="dom"]');
    if (!select) return [];
    return Array.from(select.options).map(o => ({
      value: (o.value || '').replace(/^\./, ''),
      label: (o.textContent || o.label || o.value || '').trim(),
      selected: !!o.selected
    })).filter(x => x.value || x.label);
  });
}
async function getPresetCards(page) {
  await page.waitForTimeout(1500);
  return await page.evaluate(() => {
    const nodes = Array.from(document.querySelectorAll('[data-preset-id], .preset-btn, .preset-card a, a, button'));
    return nodes.map((el, fallbackIndex) => {
      const card = el.closest('.preset-card') || el;
      const titleEl = card.querySelector?.('.preset-title, h2, h3, .title') || el;
      const rawTitle = (titleEl.innerText || titleEl.textContent || el.innerText || el.textContent || '').trim().replace(/\s+/g, ' ');
      const text = (card.innerText || card.textContent || rawTitle || '').trim().replace(/\s+/g, ' ');
      const presetId = el.getAttribute('data-preset-id') || el.getAttribute('data-preset') || el.getAttribute('data-id') || '';
      const className = String(el.className || '') + ' ' + String(card.className || '');
      return {
        index: fallbackIndex,
        presetId,
        title: rawTitle.slice(0, 160),
        text: text.slice(0, 300),
        href: el.href || el.getAttribute('href') || '',
        isPreset: !!presetId || /preset/.test(className)
      };
    }).filter(x => x.title && x.isPreset && !/^Создать сайт$/i.test(x.title));
  });
}
async function fillAddressAndDomain(page) {
  const addressSelector = '#site_address, input[name="addr"], input[placeholder*="website" i], input[aria-label*="website" i], input[type="text"]';
  await page.waitForSelector(addressSelector, { timeout: 30000 });
  await page.fill(addressSelector, SITE_PREFIX);
  const zones = await getDomainZones(page);
  if (!DOMAIN) need('domain_zone_choice', { sitePrefix: SITE_PREFIX, availableZones: zones });
  const matched = zones.find(z => z.value === DOMAIN || z.label.replace(/^\./, '') === DOMAIN || z.label.includes(DOMAIN));
  if (!matched) need('domain_zone_choice', { sitePrefix: SITE_PREFIX, requestedDomain: DOMAIN, availableZones: zones });
  const select = await page.$('select#domains, select[name="dom"]');
  if (select) {
    await select.selectOption(matched.value).catch(async () => {
      await select.selectOption({ label: matched.label }).catch(() => {});
    });
  }
  const terms = await page.$('#terms_checkbox, input[name="terms"]');
  if (terms) {
    const checked = await terms.evaluate(el => !!el.checked);
    if (!checked) await terms.click();
  }
  return { zones, selectedZone: matched };
}
async function submitCreateSite(page) {
  // Close phone popup if present before clicking submit
  await closePhonePopupIfPresent(page);

  const submitLocator = page.locator('#submit_btn, button:has-text("Создать сайт"), button[type="submit"], input[type="submit"]').first();
  const submitCount = await submitLocator.count();
  if (!submitCount) {
    need('submit_button_not_found', { url: page.url(), pageText: (await page.textContent('body').catch(() => '')).slice(0, 800) });
  }
  await submitLocator.scrollIntoViewIfNeeded().catch(() => {});
  await submitLocator.click({ force: true });
  log('Submit clicked, waiting for preset cards...');

  // uCoz updates the page via JS without a full navigation — wait for preset cards to appear
  await Promise.race([
    page.waitForSelector('[data-preset-id], .preset-btn, .preset-card', { timeout: 30000 }).catch(() => {}),
    page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 30000 }).catch(() => {}),
  ]);
  await page.waitForTimeout(2000);

  const body = await page.textContent('body').catch(() => '');
  if (/занят|существует|недоступ/i.test(body || '')) need('address_unavailable_or_existing', { sitePrefix: SITE_PREFIX, domain: DOMAIN, pageText: body.slice(0, 800) });
}
async function choosePreset(page) {
  const cards = await getPresetCards(page);
  if (!cards.length) {
    need('site_configuration_cards_not_found', {
      message: 'No live configuration cards were found on the current page; do not mark preset selection as successful.',
      url: page.url()
    });
  }
  const catalog = loadCatalog();
  const catalogMatch = resolveCatalogMatch(PRESET_MATCH || PRESET_ID, catalog);
  let selected = null;
  if (PRESET_ID) selected = cards.find(c => c.presetId === PRESET_ID);
  if (!selected && catalogMatch?.presetId) selected = cards.find(c => c.presetId === catalogMatch.presetId);
  if (!selected && PRESET_INDEX !== null && Number.isInteger(PRESET_INDEX)) selected = cards.find(c => c.index === PRESET_INDEX) || cards[PRESET_INDEX];
  if (!selected && PRESET_MATCH) {
    const rx = new RegExp(PRESET_MATCH.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'i');
    selected = cards.find(c => rx.test(c.title) || rx.test(c.text) || rx.test(c.presetId));
  }
  if (!selected) need('site_configuration_choice', { availableCards: cards, catalogPresets: catalog.presets || [] });
  // Phone popup may reappear and block pointer events on preset cards
  await closePhonePopupIfPresent(page);
  const selector = selected.presetId ? `[data-preset-id="${selected.presetId}"], [data-preset="${selected.presetId}"], [data-id="${selected.presetId}"]` : null;
  if (selector && await page.$(selector)) await page.locator(selector).first().click({ force: true });
  else await page.locator('[data-preset-id], .preset-btn, .preset-card a, a, button').nth(selected.index).click({ force: true });
  await page.waitForTimeout(2000);
  const titleInput = await page.$('#auto_preset_modal #auto-name, #auto-name, input[name="name"], input[name="title"]');
  if (titleInput) await titleInput.fill(SITE_TITLE);
  await closePhonePopupIfPresent(page);
  const submit = await page.$('#submit_btn_auto_preset, button:has-text("Создать сайт"), button:has-text("Продолжить"), button:has-text("Сохранить"), button[type="submit"], input[type="submit"]');
  if (submit) {
    // uCoz preset modal requires a real trusted click; synthetic DOM events can
    // close/ignore the modal without actually applying the site configuration.
    await page.locator('#submit_btn_auto_preset').click({ force: true }).catch(() => submit.click({ force: true }));
    await page.waitForLoadState('networkidle', { timeout: 20000 }).catch(() => {});
    await page.waitForTimeout(5000);
  }
  return { selected, cards };
}
async function closePhonePopupIfPresent(page) {
  await page.evaluate(() => {
    try {
      if (typeof window.closePhonePopup === 'function') {
        window.closePhonePopup();
        return;
      }
      const popup = document.querySelector('#ucoz_phone_popup');
      if (popup) popup.remove();
    } catch {}
  }).catch(() => {});
  await page.waitForTimeout(150);
}
async function isOnPasswordGate(page) {
  return page.evaluate(() => {
    const pass = document.querySelector('input[name="password"], input[type="password"]');
    const text = document.body?.innerText || '';
    return Boolean(pass && (/парол/i.test(text) || location.href.includes('/admin/')));
  }).catch(() => false);
}
async function tryPanelPassword(page, pwd) {
  const pass = await page.$('input[name="password"], input[type="password"]');
  if (!pass) return false;
  await pass.fill(pwd);
  const submit = await page.$('button[type="submit"], input[type="submit"], button:has-text("Войти"), button:has-text("Продолжить"), button:has-text("OK")');
  if (submit) {
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 45000 }).catch(() => {}),
      submit.click()
    ]);
  } else {
    await pass.press('Enter');
    await page.waitForLoadState('domcontentloaded', { timeout: 45000 }).catch(() => {});
  }
  await page.waitForTimeout(1500);
  return !(await isOnPasswordGate(page));
}
async function handlePanelPasswordGate(page) {
  await closePhonePopupIfPresent(page);
  await page.waitForTimeout(800);
  if (!(await isOnPasswordGate(page))) return false;
  // Try account password first
  if (await tryPanelPassword(page, PASSWORD)) return true;
  // Account password rejected — try dedicated panel password if provided
  if (PANEL_PASSWORD && PANEL_PASSWORD !== PASSWORD) {
    log('Account password rejected by panel gate; retrying with PANEL_PASSWORD.');
    if (await tryPanelPassword(page, PANEL_PASSWORD)) return true;
  }
  // Both failed — stop and ask the user
  need('panel_password_separate', {
    message: 'The site panel (/admin/) rejected all provided passwords. This site likely has a separate panel password different from the uCoz account password. Set PANEL_PASSWORD (or UCOZ_PANEL_PASSWORD) env variable with the correct panel password and retry.',
    url: page.url()
  });
}
async function extractUidLoginUrl(page) {
  return await page.evaluate(() => {
    const trigger = document.querySelector('a.uid-login, a[href*="login.uid.me"], [onclick*="login.uid.me"]');
    if (!trigger) return '';
    const href = trigger.getAttribute('href') || '';
    if (/^https?:\/\//i.test(href)) return href;
    const onclick = trigger.getAttribute('onclick') || '';
    const match = onclick.match(/window\.open\('([^']+)'/);
    return match ? match[1] : '';
  }).catch(() => '');
}
async function handlePanelUidGate(page) {
  await closePhonePopupIfPresent(page);
  await page.waitForTimeout(800);
  const hasUidGate = await page.evaluate(() => {
    const text = document.body?.innerText || '';
    return Boolean(document.querySelector('a.uid-login, a[href*="login.uid.me"], [onclick*="login.uid.me"]') && /uID|uid/i.test(text));
  }).catch(() => false);
  if (!hasUidGate) return false;
  const loginUrl = await extractUidLoginUrl(page);
  if (!loginUrl) return false;
  await page.goto(loginUrl, { waitUntil: 'domcontentloaded', timeout: 45000 }).catch(() => {});
  await page.waitForTimeout(1200);
  const uidEmail = await page.$('#uid_email, input[name="email"]');
  const uidPassword = await page.$('#uid_password, input[name="pass"], input[type="password"]');
  if (!uidEmail || !uidPassword) return false;
  await uidEmail.fill(EMAIL);
  await uidPassword.fill(PASSWORD);
  const submit = await page.$('#uid-form-submit, input[type="submit"], button[type="submit"]');
  if (submit) {
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 45000 }).catch(() => {}),
      submit.click()
    ]);
  } else {
    await uidPassword.press('Enter');
    await page.waitForLoadState('domcontentloaded', { timeout: 45000 }).catch(() => {});
  }
  await page.waitForTimeout(2000);
  return true;
}
async function handlePanelAuthGate(page) {
  if (await handlePanelPasswordGate(page)) return 'plain';
  if (await handlePanelUidGate(page)) return 'uid';
  return false;
}
async function enterPanel(page, base) {
  await page.goto(`${base}/panel/`, { waitUntil: 'domcontentloaded', timeout: 45000 });
  await page.waitForTimeout(2000);
  await closePhonePopupIfPresent(page);
  await handlePanelAuthGate(page);
}
async function listPanelModules(page, base) {
  await page.goto(`${base}/panel/?a=allmod`, { waitUntil: 'domcontentloaded', timeout: 45000 }).catch(async () => {
    await page.goto(`${base}/panel/`, { waitUntil: 'domcontentloaded', timeout: 45000 });
  });
  await page.waitForTimeout(1500);
  await closePhonePopupIfPresent(page);
  return await page.evaluate(() => {
    const parse = rootSel => {
      const root = document.querySelector(rootSel);
      if (!root) return [];
      return Array.from(root.querySelectorAll('a, .module-item, span')).map(el => (el.innerText || el.textContent || '').trim().replace(/\s+/g, ' ')).filter(Boolean);
    };
    const active = parse('.active-modules, #active-modules');
    const inactive = parse('.inactive-modules, #inactive-modules');
    const links = Array.from(document.querySelectorAll('a[href*="?a="], .module, .module-item')).map((el, index) => ({ index, text: (el.innerText || el.textContent || '').trim().replace(/\s+/g, ' ').slice(0, 140), href: el.href || el.getAttribute('href') || '' })).filter(x => x.text);
    return { active, inactive, links };
  });
}
async function getUserLogin(page, base) {
  await page.goto(`${base}/panel/?a=users&l=find`, { waitUntil: 'domcontentloaded', timeout: 45000 }).catch(() => {});
  await page.waitForTimeout(1200);
  await closePhonePopupIfPresent(page);
  return await page.evaluate(() => {
    for (const row of document.querySelectorAll('tr')) {
      const cells = row.querySelectorAll('td');
      if (cells.length > 1 && cells[0]?.textContent?.trim() === '1') return cells[1]?.textContent?.trim().replace(/\*+$/, '') || 'admin';
    }
    return 'admin';
  }).catch(() => 'admin');
}
async function setupUapi(page, base) {
  await page.goto(`${base}/panel/?a=uapi`, { waitUntil: 'domcontentloaded', timeout: 45000 });
  await page.waitForTimeout(2000);
  await closePhonePopupIfPresent(page);
  const existing = await page.evaluate(() => {
    const html = document.documentElement.innerHTML;
    return ([...html.matchAll(/data-copy="([^"]*sk_live_[^"]+)"/g)].map(m => m[1])[0]) || '';
  });
  if (existing && !REGENERATE_UAPI_KEY) return { status: 'reused', token: existing };
  const userLogin = await getUserLogin(page, base);
  await page.goto(`${base}/panel/?a=uapi`, { waitUntil: 'domcontentloaded', timeout: 45000 });
  await closePhonePopupIfPresent(page);
  const ssid = await page.evaluate(() => window.uCoz?.ssid || document.querySelector('input[name="ssid"]')?.value || '');
  const resp = await page.evaluate(async ({ KEY_NAME, userLogin, ssid }) => {
    const p = new URLSearchParams();
    p.set('name', KEY_NAME); p.set('user', userLogin); p.set('allow_cp_settings', 'on');
    p.set('a', 'uapi_keys'); p.set('l', 'create'); p.set('id', ''); p.set('ssid', ssid); p.set('_tp_', 'xml'); p.set('_wi', '1');
    const r = await fetch('/panel/', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8', 'X-Requested-With': 'XMLHttpRequest' }, body: p.toString(), credentials: 'include' });
    return { status: r.status, text: await r.text() };
  }, { KEY_NAME, userLogin, ssid });
  const token = ([...resp.text.matchAll(/data-copy="([^"]*sk_live_[^"]+)"/g)].map(m => m[1])[0]) || '';
  return { status: token ? 'created' : 'unknown_check_panel', token, userLogin };
}
function parseFtpHelperText(text, base) {
  const hostFallback = new URL(base).host;
  const clean = String(text || '').replace(/\u00a0/g, ' ').replace(/[ \t]+/g, ' ');
  const host = (clean.match(/FTP\s*host\s*[:：]\s*([^\n\r<]+)/i)?.[1] || clean.match(/FTP[-\s]*сервер\s*[:：]\s*([^\n\r<]+)/i)?.[1] || hostFallback).trim();
  const login = (clean.match(/FTP\s*login\s*[:：]\s*([A-Za-z0-9_.-]+)/i)?.[1] || clean.match(/FTP[-\s]*(?:логин|пользователь)\s*[:：]\s*([A-Za-z0-9_.-]+)/i)?.[1] || '').trim();
  const passwordStatus = (clean.match(/FTP\s*password\s*[:：]\s*([^\n\r<]+)/i)?.[1] || clean.match(/FTP[-\s]*пароль\s*[:：]\s*([^\n\r<]+)/i)?.[1] || '').trim();
  return { host, login, passwordStatus };
}
async function getFtpHelperInfo(page, base) {
  await page.goto(`${base}/panel/?a=fm`, { waitUntil: 'domcontentloaded', timeout: 45000 }).catch(async () => {
    await page.goto(`${base}/panel/`, { waitUntil: 'domcontentloaded', timeout: 45000 });
  });
  await page.waitForTimeout(1200);
  await closePhonePopupIfPresent(page);
  await handlePanelPasswordGate(page);
  await page.evaluate(() => {
    if (window.uCoz?.utils?.helper) window.uCoz.utils.helper(1);
    else if (window.uCoz?.utils?.help) window.uCoz.utils.help(1);
  }).catch(() => {});
  await page.waitForTimeout(1500);
  const text = await page.evaluate(() => {
    const selectors = ['.u-help', '.u-tooltip', '.tooltip', '.uz-helper', '.popup', '.modal', '#uHelper', '#helper', 'body'];
    return selectors.map(sel => Array.from(document.querySelectorAll(sel)).map(el => el.innerText || el.textContent || '').join('\n')).join('\n');
  }).catch(async () => await page.textContent('body').catch(() => ''));
  const parsed = parseFtpHelperText(text, base);
  return { ...parsed, found: Boolean(parsed.login || /FTP/i.test(text || '')) };
}
async function setupFtp(page, base) {
  let helper = await getFtpHelperInfo(page, base).catch(() => ({ host: new URL(base).host, login: '', passwordStatus: '', found: false }));
  await page.goto(`${base}/panel/?a=ftppass`, { waitUntil: 'domcontentloaded', timeout: 45000 });
  await page.waitForTimeout(1500);
  await closePhonePopupIfPresent(page);
  await handlePanelPasswordGate(page);
  const pageText = await page.textContent('body').catch(() => '');
  const needsSecretAnswer = /секретн|ответ на вопрос|squa/i.test(pageText || '') && !SECRET_ANSWER;
  if (needsSecretAnswer) return { status: 'needs_secret_answer', ...helper };
  const maybeAlreadySet = /установлен|изменить|сменить|FTP/i.test(pageText || '');
  if (maybeAlreadySet && !FORCE_FTP_OVERWRITE && !FTP_PASSWORD) return { status: 'existing_password_unknown_not_overwritten', ...helper };
  if (!FTP_PASSWORD) return { status: 'needs_ftp_password', ...helper };
  const ssid = await page.evaluate(() => window.uCoz?.ssid || document.querySelector('input[name="ssid"]')?.value || '');
  const resp = await page.evaluate(async ({ FTP_PASSWORD, SECRET_ANSWER, ssid }) => {
    const p = new URLSearchParams();
    p.set('a', 'ftppass'); p.set('password', FTP_PASSWORD); p.set('password1', FTP_PASSWORD);
    if (SECRET_ANSWER) p.set('squa', SECRET_ANSWER);
    p.set('ssid', ssid); p.set('_tp_', 'xml'); p.set('_wi', '1');
    const r = await fetch('/panel/', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8', 'X-Requested-With': 'XMLHttpRequest' }, body: p.toString(), credentials: 'include' });
    return { status: r.status, text: await r.text() };
  }, { FTP_PASSWORD, SECRET_ANSWER, ssid });
  const ok = /setSaveSuccessIcon|успеш|success/i.test(resp.text);
  const after = await getFtpHelperInfo(page, base).catch(() => helper);
  helper = after?.found ? after : helper;
  return { status: ok ? (FORCE_FTP_OVERWRITE ? 'overwritten' : 'configured') : 'unknown_check_panel', ...helper };
}

(async () => {
  const result = { ok: true, mode: MODE, timestamp: new Date().toISOString() };
  const browser = await chromium.launch({ headless: HEADLESS, slowMo: SLOW_MO });
  const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
  const page = await context.newPage();
  try {
    await login(page);
    if (MODE === 'discover') {
      await openCreateSite(page);
      result.domainZones = await getDomainZones(page);
      result.catalogPresets = loadCatalog().presets || [];
      result.note = 'Discovery only. Ask user for prefix/domain/card before MODE=new.';
      console.log(JSON.stringify(result, null, 2));
      return;
    }
    let base = BASE_URL;
    if (MODE === 'new') {
      base = `https://${SITE_PREFIX}.${DOMAIN}`;
      result.site = base + '/';
      if (PRESET_MATCH || PRESET_ID || PRESET_INDEX !== null) {
        // Resume-safe path: if a previous run already created the site and stopped at
        // configuration choice, enter the site panel first to pass possible /admin/
        // password gates, then open the configuration page.
        await enterPanel(page, base);
        await page.goto(`${base}/panel/?a=cp`, { waitUntil: 'domcontentloaded', timeout: 45000 }).catch(() => null);
        await page.waitForTimeout(2000);
        // Phone popup can reappear after panel navigation — close it again
        await closePhonePopupIfPresent(page);
      }
      let cards = await getPresetCards(page);
      if (!cards.length && !(PRESET_MATCH || PRESET_ID || PRESET_INDEX !== null)) {
        await openCreateSite(page);
        const domainResult = await fillAddressAndDomain(page);
        result.domainZones = domainResult.zones;
        result.selectedZone = domainResult.selectedZone;
        await submitCreateSite(page);
      }
      const presetResult = await choosePreset(page);
      result.availableCards = presetResult.cards;
      result.selectedCard = presetResult.selected;
    }
    if (MODE === 'existing') result.site = base + '/';
    await enterPanel(page, base);
    result.modulesSeen = await listPanelModules(page, base);
    if (SETUP_UAPI) {
      const uapi = await setupUapi(page, base);
      result.uapi = { ...uapi, token: redact(uapi.token) };
      if (OUTPUT_SECRETS) result.rawApiToken = uapi.token;
    }
    if (SETUP_FTP) {
      const ftp = await setupFtp(page, base);
      result.ftp = { ...ftp, password: FTP_PASSWORD ? redact(FTP_PASSWORD) : undefined };
    }
    if (SETUP_UAPI || SETUP_FTP) {
      const host = new URL(base).host;
      result.mcp = {
        command: 'npx', args: ['-y', 'ucoz-mcp@latest'],
        env: {
          UCOZ_API_TOKEN: result.uapi?.token || '<set-token>',
          UCOZ_SITE_URL: base + '/',
          UCOZ_FTP_HOST: result.ftp?.host || host,
          UCOZ_FTP_USER: result.ftp?.login || '<ftp-user-from-helper>',
          UCOZ_FTP_PASS: FTP_PASSWORD ? redact(FTP_PASSWORD) : '<set-ftp-password>'
        }
      };
    }
    fs.mkdirSync(OUT_DIR, { recursive: true });
    fs.writeFileSync(path.join(OUT_DIR, 'provision-result.json'), JSON.stringify(result, null, 2));
    console.log(JSON.stringify(result, null, 2));
  } catch (err) {
    fail(err.message || String(err), { url: page.url() });
  } finally {
    await browser.close();
  }
})();

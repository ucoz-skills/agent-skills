'use strict';
/**
 * Entry stub of a Node.js app for uCoz Server Scripts (Passenger) — keep it this small.
 * App root:   domains/{domain}/app_node/{name}/app.js   (logic lives in lib/main.js)
 * Create:     apps_tool node_create app_root=domains/{domain}/app_node/{name} app_uri=/{name}
 *                       version=22 startup_file=app.js env_vars={"APP_NAME":"{name}"}   (APP_NAME is required)
 * Public URL: https://{site}/apps/{name}/
 * Reload after every change: apps_tool node_restart, or files_write {app_root}/tmp/restart.txt.
 *
 * Why a stub (verified): console output and Passenger logs are not readable through MCP, and a startup
 * crash shows only Passenger's "Web application could not be started … Error ID" page. Here a crash in
 * lib/main.js (syntax error, missing module, missing APP_NAME) is written with its stack to
 * logs/startup.log and every request answers JSON 500 {"error":"startup","detail":"…"}.
 * Passenger hooks listen(); PORT is not set, so the fallback port is never really used.
 */
const fs = require('fs');
const path = require('path');
const http = require('http');

let handler;
try {
  handler = require('./lib/main.js');
  if (typeof handler !== 'function') throw new Error('lib/main.js must export a (req, res) handler');
} catch (e) {
  try {
    fs.mkdirSync(path.join(__dirname, 'logs'), { recursive: true });
    fs.appendFileSync(path.join(__dirname, 'logs', 'startup.log'),
      new Date().toISOString() + ' ' + String((e && e.stack) || e) + '\n');
  } catch (_) { /* logging must never crash the stub */ }
  const detail = String((e && e.message) || e).slice(0, 200);
  handler = (req, res) => {
    res.writeHead(500, { 'Content-Type': 'application/json; charset=utf-8', 'Cache-Control': 'no-store' });
    res.end(JSON.stringify({ ok: false, error: 'startup', detail }));
  };
}

http.createServer(handler).listen(process.env.PORT || 3000);

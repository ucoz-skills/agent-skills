"""
App logic, imported by the entry stub passenger_wsgi.py (which logs startup crashes to logs/startup.log).
Standard library only. Packages: see references/PYTHON.md §5 (cron pip job) and import them lazily.

  GET  /health     -> {"ok": true, "build": ..., "pid": ..., "uptime": ...}
  POST /api/echo   -> {"text": "..."} -> echoes validated input (+ visitor id if logged in)
  GET  /api/cron   -> cron target, header X-Cron-Key

Passenger runs several processes (wsgi.multiprocess): the pid differs between requests, so a restart is
proven by the BUILD marker and a small uptime, not by the pid.
"""
import hashlib
import hmac
import json
import os
import sys
import time

# Bump on every deploy: /health shows it, which proves the reload picked up the new code.
BUILD = "2026-10-08.1"

NAME = os.environ.get("APP_NAME", "")
if not NAME or NAME == "{name}":
    # Fails loudly: the stub turns this into JSON 500 {"error": "startup"} + logs/startup.log.
    raise RuntimeError('APP_NAME env var is missing: pass env_vars={"APP_NAME":"<name>"} to python_create')

DIR = os.path.dirname(os.path.abspath(__file__))
LOG_FILE = os.path.join(DIR, "logs", "app.log")
BODY_MAX = 100 * 1024
STARTED = time.time()


# ---------- logging (print/stderr is not visible through MCP) ----------
def log(event, **ctx):
    try:
        os.makedirs(os.path.dirname(LOG_FILE), exist_ok=True)
        if os.path.exists(LOG_FILE) and os.path.getsize(LOG_FILE) > 1_048_576:
            os.replace(LOG_FILE, LOG_FILE + ".1")
        with open(LOG_FILE, "a", encoding="utf-8") as f:
            f.write(f"{time.strftime('%Y-%m-%dT%H:%M:%S')} {os.getpid()} {event} {json.dumps(ctx, ensure_ascii=False)}\n")
    except Exception:
        pass  # logging must never crash the app


# ---------- config (app root, outside public_html, files_chmod "384", never served) ----------
def _load_config():
    try:
        with open(os.path.join(DIR, "config.json"), encoding="utf-8") as f:
            return json.load(f)
    except Exception:
        return {}


CONFIG = _load_config()


class HttpError(Exception):
    def __init__(self, status, code, message=""):
        super().__init__(code)
        self.status, self.code, self.message = status, code, message


_REASONS = {200: "OK", 202: "Accepted", 400: "Bad Request", 401: "Unauthorized", 403: "Forbidden",
            404: "Not Found", 405: "Method Not Allowed", 413: "Payload Too Large", 429: "Too Many Requests",
            500: "Internal Server Error", 503: "Service Unavailable"}


def _json(start_response, status, obj):
    body = json.dumps(obj, ensure_ascii=False).encode("utf-8")
    start_response(f"{status} {_REASONS.get(status, 'Unknown')}", [
        ("Content-Type", "application/json; charset=utf-8"),
        ("Cache-Control", "no-store"),
        ("X-Content-Type-Options", "nosniff"),
        ("Content-Length", str(len(body))),
    ])
    return [body]


def route_path(environ):
    """Path inside the app. Passenger already strips the app_uri: SCRIPT_NAME=/{name}, PATH_INFO=/x."""
    p = environ.get("PATH_INFO") or "/"
    return "/" + p.strip("/") if p.strip("/") else "/"


def read_json(environ):
    try:
        length = int(environ.get("CONTENT_LENGTH") or 0)
    except ValueError:
        length = 0
    if length > BODY_MAX:
        raise HttpError(413, "too_large")
    raw = environ["wsgi.input"].read(length) if length else b""
    if not raw:
        return {}
    try:
        data = json.loads(raw.decode("utf-8"))
    except Exception:
        raise HttpError(400, "bad_json")
    if not isinstance(data, dict):
        raise HttpError(400, "bad_json")
    return data


def require_same_origin(environ):
    origin = environ.get("HTTP_ORIGIN", "")
    if origin and origin not in CONFIG.get("origins", []):
        raise HttpError(403, "origin")


def require_key(environ, config_name, header):
    """Shared-secret check for cron/admin routes. Fails CLOSED on a missing/short secret; 404 hides the route.
    Compares SHA-256 digests of bytes, so non-ASCII input cannot raise."""
    expected = str(CONFIG.get(config_name, ""))
    given = environ.get("HTTP_" + header.upper().replace("-", "_"), "")
    digest = lambda s: hashlib.sha256(s.encode("utf-8", "surrogateescape")).digest()  # noqa: E731
    if len(expected) < 16 or not hmac.compare_digest(digest(given), digest(expected)):
        raise HttpError(404, "not_found")


def current_user(environ):
    """Visitor identity via the platform helper (references/IDENTITY.md). None = guest.
    `ucoz` is /opt/ucoz/lib/ucoz.py (PYTHONPATH under Passenger; not available in cron)."""
    try:
        from ucoz import ucoz_user  # lazy: a missing helper is not fatal
    except Exception:
        return None
    u = ucoz_user(environ) or {}
    if not u.get("id"):
        return None
    return {"id": int(u["id"]), "login": str(u.get("user", "")), "gid": int(u.get("gid") or 0)}


# ---------- handlers ----------
def health(environ):
    return {"ok": True, "build": BUILD, "python": sys.version.split()[0], "pid": os.getpid(),
            "uptime": int(time.time() - STARTED)}


def echo(environ):
    require_same_origin(environ)
    data = read_json(environ)
    text = str(data.get("text", "")).strip()
    if not text or len(text) > 500:
        raise HttpError(400, "text", "Text is required (up to 500 characters).")
    user = current_user(environ)
    return {"ok": True, "text": text, "uid": user["id"] if user else None}


def cron(environ):
    require_key(environ, "cron_key", "X-Cron-Key")
    log("cron")
    return {"ok": True}


ROUTES = {
    ("GET", "/health"): health,
    ("POST", "/api/echo"): echo,
    ("GET", "/api/cron"): cron,
}


def application(environ, start_response):
    method = environ.get("REQUEST_METHOD", "GET")
    path = route_path(environ)
    try:
        handler = ROUTES.get((method, path))
        if not handler:
            raise HttpError(404, "not_found")
        return _json(start_response, 200, handler(environ))
    except HttpError as e:
        return _json(start_response, e.status, {"ok": False, "error": e.code, "message": e.message})
    except Exception as e:  # never leak details to the client
        log("error", method=method, path=path, msg=str(e)[:300])
        return _json(start_response, 500, {"ok": False, "error": "server"})

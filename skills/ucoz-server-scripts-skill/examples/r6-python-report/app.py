"""
Recipe R6 — web part: admins of the site list and download the reports that report.py builds from cron.
Imported by the entry stub passenger_wsgi.py (templates/python). Standard library only.

  GET /health                 -> {"ok": true, "build": ..., "last": {...from data/last.json}}
  GET /                       -> staff only: HTML list of reports
  GET /api/reports            -> staff only: JSON list
  GET /report/<file>.xlsx     -> staff only: download
"""
import json
import os
import re
import sys
import time
from html import escape

BUILD = "2026-10-08.1"

NAME = os.environ.get("APP_NAME", "")
if not NAME or NAME == "{name}":
    raise RuntimeError('APP_NAME env var is missing: pass env_vars={"APP_NAME":"<name>"} to python_create')

DIR = os.path.dirname(os.path.abspath(__file__))
REPORTS = os.path.join(DIR, "data", "reports")
STATE = os.path.join(DIR, "data", "last.json")
BASE = f"/apps/{NAME}/"
FILE_RE = re.compile(r"^blog-report-\d{4}-\d{2}-\d{2}\.xlsx$")
XLSX = "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
STARTED = time.time()


def _config():
    try:
        with open(os.path.join(DIR, "config.json"), encoding="utf-8") as f:
            return json.load(f)
    except Exception:
        return {}


CONFIG = _config()


class HttpError(Exception):
    def __init__(self, status, code, message=""):
        super().__init__(code)
        self.status, self.code, self.message = status, code, message


_REASONS = {200: "OK", 401: "Unauthorized", 403: "Forbidden", 404: "Not Found", 405: "Method Not Allowed",
            500: "Internal Server Error"}


def _send(start_response, status, body, ctype, extra=()):
    start_response(f"{status} {_REASONS.get(status, 'Unknown')}", [
        ("Content-Type", ctype), ("Cache-Control", "no-store"), ("X-Content-Type-Options", "nosniff"),
        ("Content-Length", str(len(body))), *extra])
    return [body]


def _json(start_response, status, obj):
    return _send(start_response, status, json.dumps(obj, ensure_ascii=False).encode("utf-8"), "application/json; charset=utf-8")


def current_user(environ):
    try:
        from ucoz import ucoz_user   # platform helper, present under Passenger only
    except Exception:
        return None
    u = ucoz_user(environ) or {}
    return {"id": int(u["id"]), "gid": int(u.get("gid") or 0)} if u.get("id") else None


def require_staff(environ):
    u = current_user(environ)
    if not u:
        raise HttpError(401, "auth", "Log in to the site as an administrator.")
    if u["gid"] not in CONFIG.get("staff_gids", []):
        raise HttpError(403, "forbidden")
    return u


def reports():
    if not os.path.isdir(REPORTS):
        return []
    out = []
    for f in sorted(os.listdir(REPORTS), reverse=True):
        if FILE_RE.match(f):
            st = os.stat(os.path.join(REPORTS, f))
            out.append({"file": f, "bytes": st.st_size, "built": time.strftime("%Y-%m-%d %H:%M", time.localtime(st.st_mtime))})
    return out


def last_state():
    try:
        with open(STATE, encoding="utf-8") as f:
            return json.load(f)
    except Exception:
        return None


def page(environ, start_response):
    require_staff(environ)
    items = "".join(
        f'<li><a href="{escape(BASE)}report/{escape(r["file"])}">{escape(r["file"])}</a> '
        f'<span>{r["bytes"] // 1024} KB · {escape(r["built"])}</span></li>' for r in reports()) or "<li>No reports yet.</li>"
    html = (f'<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
            f'<title>Blog reports</title><style>body{{font:16px/1.6 system-ui,sans-serif;max-width:640px;margin:40px auto;padding:0 16px}}'
            f'li{{margin:6px 0}}span{{color:#888;font-size:14px;margin-left:8px}}</style></head>'
            f'<body><h1>Blog reports</h1><p>Built every Monday from cron.</p><ul>{items}</ul></body></html>')
    return _send(start_response, 200, html.encode("utf-8"), "text/html; charset=utf-8")


def download(environ, start_response, name):
    require_staff(environ)
    if not FILE_RE.match(name):
        raise HttpError(404, "not_found")
    path = os.path.join(REPORTS, name)
    if not os.path.isfile(path):
        raise HttpError(404, "not_found")
    with open(path, "rb") as f:
        body = f.read()
    return _send(start_response, 200, body, XLSX, [("Content-Disposition", f'attachment; filename="{name}"')])


def application(environ, start_response):
    method = environ.get("REQUEST_METHOD", "GET")
    path = "/" + (environ.get("PATH_INFO") or "/").strip("/")
    try:
        if method != "GET":
            raise HttpError(405, "method")
        if path == "/health":
            return _json(start_response, 200, {"ok": True, "build": BUILD, "python": sys.version.split()[0],
                                               "uptime": int(time.time() - STARTED), "last": last_state()})
        if path == "/":
            return page(environ, start_response)
        if path == "/api/reports":
            require_staff(environ)
            return _json(start_response, 200, {"ok": True, "reports": reports()})
        if path.startswith("/report/"):
            return download(environ, start_response, path[len("/report/"):])
        raise HttpError(404, "not_found")
    except HttpError as e:
        return _json(start_response, e.status, {"ok": False, "error": e.code, "message": e.message})
    except Exception:
        return _json(start_response, 500, {"ok": False, "error": "server"})

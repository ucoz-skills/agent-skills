"""
Entry stub of a Python (WSGI) app for uCoz Server Scripts (Passenger) — keep it this small.
App root:   domains/{domain}/app_python/{name}/passenger_wsgi.py   (logic lives in app.py)
Create:     apps_write python_create app_root=domains/{domain}/app_python/{name} app_uri=/{name}
                      version=3.11 startup_file=passenger_wsgi.py entry_point=application
                      env_vars={"APP_NAME":"{name}"}          (APP_NAME is required)
            THEN write this file (python_create replaces passenger_wsgi.py with its own stub).
Public URL: https://{site}/apps/{name}/
Reload after every change: apps_write python_restart, or files_write {app_root}/tmp/restart.txt.

Why a stub (verified): print()/stderr and Passenger logs are not readable through MCP, and a startup
crash shows only Passenger's "Web application could not be started … Error ID" page. Here a crash while
importing app.py (syntax error, missing package, missing APP_NAME) is written with its traceback to
logs/startup.log and every request answers JSON 500 {"error": "startup", "detail": "..."}.
"""
import json
import os
import sys
import time
import traceback

DIR = os.path.dirname(os.path.abspath(__file__))
if DIR not in sys.path:
    sys.path.insert(0, DIR)

try:
    from app import application  # noqa: F401  (the real WSGI callable)
except Exception:
    _detail = traceback.format_exception_only(*sys.exc_info()[:2])[-1].strip()[:200]
    try:
        os.makedirs(os.path.join(DIR, "logs"), exist_ok=True)
        with open(os.path.join(DIR, "logs", "startup.log"), "a", encoding="utf-8") as f:
            f.write(time.strftime("%Y-%m-%dT%H:%M:%S ") + traceback.format_exc() + "\n")
    except Exception:
        pass  # logging must never crash the stub

    def application(environ, start_response):
        body = json.dumps({"ok": False, "error": "startup", "detail": _detail}).encode("utf-8")
        start_response("500 Internal Server Error", [
            ("Content-Type", "application/json; charset=utf-8"),
            ("Cache-Control", "no-store"),
            ("Content-Length", str(len(body))),
        ])
        return [body]

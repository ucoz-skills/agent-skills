# MySQL

## 1. Do you need MySQL?

| Data | Use |
|---|---|
| A few hundred records, one writer, config-like | JSON file outside the docroot (atomic write) |
| Single-process app, moderate data, no admin UI needed | SQLite (`pdo_sqlite` is enabled) in `{name}_data/` |
| Concurrent writers, uniqueness rules, reports, phpMyAdmin access for the owner | **MySQL** |

There are 3 databases per account (observed). Share one database across modules with table
prefixes (`leads_`, `votes_`) instead of creating a database per module.

## 2. Create

```text
apps_tool db_create_with_user  name=main            → DB {username}_main + user {username}_main, full privileges
```

- The response contains the **password** (generated when you omit `password`). Write it immediately
  into the module's secrets file. Do not show it in chat, and do not ask the user to copy it.
- Host is `localhost`; the server is MariaDB 10.6. Use `utf8mb4` in your DDL and connection.
- **`db_create_user` does not grant anything** — even with a database given, the new user got
  `ER_DBACCESS_DENIED_ERROR` (verified). For a new database use `db_create_with_user`; for an extra user on
  an existing database follow `db_create_user` with `db_change_privs database=… dbuser=… confirm:true`
  (**C**, replaces the privileges with the full set).

## 3. The prefix rule

DirectAdmin prefixes **both** database and user names with `{username}_`. Your code must use the
full names (`{username}_main`). Symptom of a missing prefix: `Access denied for user 'main'@'localhost'`.

## 4. Schema: MCP cannot run SQL

`apps_tool` has no "execute query" action. Create and evolve the schema in one of two ways:

**A. Idempotent migrations in code (preferred).** Keep the DDL in the module and run it on first use:

```php
function migrate(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS app_meta (k VARCHAR(64) PRIMARY KEY, v VARCHAR(255) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $ver = (int)($pdo->query("SELECT v FROM app_meta WHERE k='schema'")->fetchColumn() ?: 0);
    $steps = [
        1 => "CREATE TABLE leads_items (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(120) NOT NULL,
                email VARCHAR(190) NOT NULL,
                message TEXT NOT NULL,
                uid INT UNSIGNED NULL,
                ip VARBINARY(16) NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_created (created_at)
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        2 => "ALTER TABLE leads_items ADD COLUMN status ENUM('new','done','spam') NOT NULL DEFAULT 'new'",
    ];
    foreach ($steps as $n => $sql) {
        if ($n <= $ver) continue;
        $pdo->exec($sql);
        $pdo->prepare("REPLACE INTO app_meta (k, v) VALUES ('schema', ?)")->execute([(string)$n]);
    }
}
```

Run it either lazily (cheap version check in bootstrap, migrate when behind) or from a protected
`?action=migrate` endpoint you call once and then verify. DDL auto-commits in MySQL, so keep each step small.

## 5. Connect

PHP:

```php
$db  = $secrets['db'];
$pdo = new PDO("mysql:host={$db['host']};dbname={$db['name']};charset=utf8mb4", $db['user'], $db['pass'], [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
]);
```

Node (verified: `mysql2` in `package.json` → `node_install_modules` → reload; connect + query ~40 ms):

```js
// lib/db.js — config.json: {"db": {"name": "{username}_main", "user": "{username}_main", "pass": "…"}}
const mysql = require('mysql2/promise');
let pool;
function db(cfg) {
  if (!pool) pool = mysql.createPool({ host: 'localhost', user: cfg.db.user, password: cfg.db.pass,
    database: cfg.db.name, charset: 'utf8mb4', connectionLimit: 3, timezone: 'Z' });
  return pool;
}
// const [rows] = await db(CONFIG).execute('SELECT id FROM leads_items WHERE id = ?', [id]);
module.exports = { db };
```

Python (verified: PyMySQL installed with the cron `pip` job from PYTHON.md §5; import it lazily):

```python
def db():
    import pymysql  # lazy: a missing package becomes a JSON error, not a startup crash
    c = CONFIG["db"]
    return pymysql.connect(host="localhost", user=c["user"], password=c["pass"], database=c["name"],
                           charset="utf8mb4", cursorclass=pymysql.cursors.DictCursor, autocommit=True)
```

Keep pools small. Without packages, Python can use `sqlite3` (standard library) instead.

## 6. Correctness patterns

- **Prepared statements only.** Never interpolate input into SQL. For dynamic `ORDER BY`, use a whitelist.
- **No double submissions:** a `UNIQUE` key on the business key (`(event_id, uid)`, `(table_id, date, slot)`).
  Catch SQLSTATE `23000` / error 1062 → "already exists".
- **Check-then-write:** `BEGIN` → `SELECT … FOR UPDATE` → validate → write → `COMMIT`. Roll back on any exception.
- **Counters:** `UPDATE t SET n = n + 1 WHERE id = ?`, never read-modify-write in code.
- **Time:** store `DATETIME` in UTC (`SET time_zone = '+00:00'` after connect). Convert for display.
- **Pagination:** keyset (`WHERE id < ? ORDER BY id DESC LIMIT 20`) for feeds. `LIMIT/OFFSET` is fine for admin tables.
- **Personal data:** store only what the feature needs. Hash IPs (`inet_pton` + HMAC) when you only need rate limiting.

## 7. Export, import, delete

- SQL never passes through MCP: the module owns its schema (`migrate()`).
- Before `db_delete` (**C**), say clearly that the data is gone afterwards.
  `db_delete` removes the database but **not its user** (verified). Also run `db_delete_user` (**C**) and
  check `db_users`.
- Verified live: `db_create_with_user` → PDO connect → idempotent `migrate()` created tables on the first
  request → inserts, `UNIQUE` duplicate detection (SQLSTATE 23000), `GET_LOCK` for cron workers; the same
  database from Node (`mysql2`) and Python (PyMySQL).

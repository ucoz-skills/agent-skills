# Template Language Reference

## Variables

**Syntax:** `$VARNAME$` — uppercase A–Z, digits 0–9, and `_` only; must start with a letter.

**Silent failure:** an undefined variable does not cause an error — it renders **literally as text** (e.g. `$SOME_VAR$` appears on the live page). This is easy to miss in review.

### Finding valid variable names

Never invent or guess a variable name by analogy with another template or module. Before using any `$VARIABLE$`, confirm it exists via:

1. `get_variables` (`templates_tool` action) — lists variables available for the selected module/template.
2. The output of `read_template` — if the variable is already used in that template's source, it is safe to reuse.

**Precedence when sources disagree:**
1. Variable present in the **same template's current source** (from `read_template`) → treat as real, regardless of whether it also appears in the variable list.
2. Variable **not used anywhere yet and not in the variable list** → do not invent it. If you suspect the list is incomplete, verify by checking the live rendered page for that variable name appearing as literal `$VAR$` text.
3. Never fall back to "a similarly-named variable from another module probably works here".

> The variable list from `get_variables` can be incomplete — variables already functioning in the live template may be omitted. The live rendered page is the ultimate source of truth.

---

## Common global variables

These appear frequently but are **not guaranteed in every template**. Always verify per-template.

**User & session:**

| Variable | Meaning |
|---|---|
| `$USERNAME$` | Username (login) |
| `$USER_ID$` | User ID (0 if guest) |
| `$USER_GROUP$` | User's group name |
| `$GROUP_ID$` | User's group ID |
| `$USER_LOGGED_IN$` | Truthy/falsy login flag |

**Navigation & links:**

| Variable | Meaning |
|---|---|
| `$HOME_PAGE_LINK$` | Site homepage URL |
| `$LOGIN_LINK$` | Login link |
| `$LOGOUT_LINK$` | Logout link |
| `$REGISTER_LINK$` | Registration link |
| `$PERSONAL_PAGE_LINK$` | User's personal page link |

**Online:**

| Variable | Meaning |
|---|---|
| `$ONLINE_COUNTER$` | Count of users currently online |
| `$ONLINE_USERS_LIST$` | List of users currently on the site |

**Messages:**

| Variable | Meaning |
|---|---|
| `$UNREAD_PM$` | Count of unread private messages |
| `$IS_NEW_PM$` | Flag: any new messages |

**Site info:**

| Variable | Meaning |
|---|---|
| `$SITE_NAME$` | Site name |
| `$COUNTER$` | Visit counter (Statistics module) |

**Date/time:**

| Variable | Meaning |
|---|---|
| `$DATE$` | Current date |
| `$TIME$` | Current time |
| `$HOUR$` | Current hour, 0–23 |
| `$DAY$` | Current day of month, 1–31 |
| `$MONTH$` | Current month, 1–12 |
| `$YEAR$` | Current year |
| `$WDAY$` | Current day of week |

**System:**

| Variable | Meaning |
|---|---|
| `$IP_ADDRESS$` | Visitor IP address |
| `$POWERED_BY$` | Required uCoz platform link — must never be removed or hidden |
| `$MODULE_ID$` | Current module identifier |
| `$PAGE_ID$` | Current page identifier — useful in conditionals |
| `$REQUEST_URI$` | Full URL of the current page |

**Page-editor content:**

| Variable | Meaning |
|---|---|
| `$CONTENT$` | Page content |
| `$ID$` | Page ID |
| `$TITLE$` | Entry title |
| `$MESSAGE$` | Entry body text |
| `$READS$` | View count |

**Feature-specific output (only meaningful where wired up):**

| Variable | Meaning |
|---|---|
| `$NMENU_<ID>$` | Vertical menu output |
| `$SMENU_<ID>$` | Horizontal menu output |
| `$MFORM_<ID>$` | Mail form output |
| `$SECURITY_CODE$` | Captcha field in a mail email template |
| `$GLOBAL_<NAME>$` | Global block output |

---

## Expressions

**Syntax:** `<? expression ?>` — evaluates the expression and inserts the result inline.

```
<? 3+2 ?>                     → 5
<? $YEAR$+10 ?>                → 2030  (if $YEAR$=2020)
<? substr($USERNAME$,0,1) ?>   → "A"   (if $USERNAME$='Admin')
```

---

## Conditionals

```
<?if(expression)?>  true_branch  <?else?>  false_branch  <?endif?>
<?ifnot(expression)?>  true_branch  <?else?>  false_branch  <?endif?>
```

- `<?else?>` and the false branch are optional.
- Maximum nesting depth: **10 levels**.
- `<?if?>` checks truthiness; `<?ifnot?>` checks falsiness.

**No `<?elseif?>`** — uCoz does not support it. Never use it; the parser may produce multiple branches or behave unpredictably. For three or more outcomes, nest `<?if?>` inside the preceding `<?else?>` branch.

```html
<?if($CID$=1 or $CID$=2)?>Free
<?else?><?if($CID$=4)?>Plus
<?else?><?if($CID$=3)?>Pro<?endif?><?endif?><?endif?>
```

Every permitted value must be checked explicitly — never use a catch-all `<?else?>` for access control logic; unknown/new values must not receive access silently.

---

## Data types

- **Strings:** `'text'` or `"text"`. Escape: `\'` `\"` `\\`. Cannot use `?>` inside a string literal.
- **Integers:** `123`, `-456`, `0b1010` (binary), `0o777` (octal), `0xFF` (hex).
- **Floats:** `3.14`, `1.5e6`, `2e-4`. Integer literals exceeding the integer range auto-promote to float.

---

## Arithmetic operators

| Op | Notes |
|---|---|
| `+` `-` `*` | Int result if both operands are int |
| `/` | **Always returns float** |
| `%` | Operands coerced to int; result int |
| `**` | Always returns float |
| unary `-` | Negation |
| unary `+` | No-op |

---

## Comparison operators

Result is `1` (true) or `0` (false).

| Op | Notes |
|---|---|
| `=` / `==` | Both strings → char-by-char, case-sensitive. Both ints → integer compare. Otherwise → float compare. |
| `!=` | Inequality |
| `<` `>` `<=` `>=` | Ordering |

```
<?if("1.000"="1.0")?>     → false (different strings)
<?if("1.000"="1.0"+0)?>   → true  (numeric coercion)
```

---

## Logical operators

| Op | Aliases |
|---|---|
| `not` | `!` |
| `and` | `&&` |
| `or` | `\|\|` |

Result is `1` or `0`.

---

## Bitwise operators

Operands coerced to integers; result is integer.

`~` (NOT), `&` (AND), `|` (OR), `^` (XOR).

---

## Operator precedence (highest to lowest)

1. unary `+`, `-`, `~`, `not`, `!`, all functions
2. `**`
3. `*`, `/`, `%`
4. `+`, `-`
5. `=`, `==`, `!=`, `<=`, `<`, `>=`, `>`
6. `&`
7. `|`, `^`
8. `&&`, `and`
9. `||`, `or`

Use parentheses to override: `<? 2*(5+3) ?>` → `16`.

---

## Truthiness

**Falsy:** empty string `''`, the single-character string `'0'`, integer `0`, float `0.0`.

**Truthy:** any non-empty string other than `'0'` — note that `'00'`, `'0.0'`, and `'false'` are **all truthy**; any non-zero number.

---

## Built-in functions

| Function | Behavior |
|---|---|
| `int(N)` | Integer part, rounds toward zero. `int(3.9)` → `3`, `int(-3.9)` → `-3` |
| `abs(N)` | Absolute value. `abs(-5)` → `5`, `abs(-3.7)` → `3.7` |
| `rnd()` | Float in `[0.0, 1.0)` |
| `rnd(N)` (N>0) | Int in `[0, N-1]`; `rnd(N)` (N<0) → int in `[N+1, 0]`; `rnd(0)` same as `rnd()` |
| `rnd(N.0)` | Float in `[0.0, N.0)` |
| `min(…)` / `max(…)` | Always returns float. `min(3,1,2)` → `1.0` |
| `len(S)` | String length (int) |
| `strpos(S1, S2)` | First index of S2 in S1 (0-based), `-1` if not found, case-sensitive. S1 limited to first 1000 chars, S2 to first 100. |
| `strrpos(S1, S2)` | Last index; same limits |
| `substr(S, N1)` | Substring from N1 to end (0-based; negative N1 counts from end) |
| `substr(S, N1, N2)` | Length N2; negative N2 sets end position from string end |

**`$JSENCODE$`** — escapes a variable for safe output inside a JavaScript string literal. Use whenever inserting a uCoz variable into inline `<script>` JS:

```
<?$JSENCODE$(text)?>
```

---

## Module calls

**Syntax:** `<?$MODULE$(param1, param2)?>`

```
<?$CATALOG$(2,5)?>    → renders the catalog, category 2, 5 items
```

---

## Type coercion

**String → number:** takes the numeric prefix; result is always float. `'42abc'` → `42.0`, `'hello'` → `0.0`. Alternate bases (`0x`, `0b`, `0o`) are **not** recognized during string→number coercion.

**Number → string:** integers convert without precision loss; floats convert to up to 15 significant digits.

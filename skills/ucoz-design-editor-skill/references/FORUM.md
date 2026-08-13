# Forum Module (`module_id=8`)

Canonical rules for the uCoz Forum module. UI patterns (glass styling, etc.) → [../EXAMPLES.md](../EXAMPLES.md#forum-glass-apple).

Forum is **not** a standard content-module chain (blog/news). It uses its own page shell, category/topic row fragments, post cards, and a JS-wired `gTable` compose form.

---

## Template map

| ID | Name | Role |
|---|---|---|
| `8/1` | Forum page shell | Full-page HTML for **all** forum views. Wraps `$BODY$`, top nav links, `$GLOBAL_AHEADER$` / `$GLOBAL_BFOOTER$`. |
| `8/2` | Post / material view | **Fragment** — one post card inside a thread (`$MESSAGE$`, author block, manage bars). |
| `8/3` | Add/edit message form | **Fragment** — compose/edit form (`#frM53` `gTable`). Do **not** restructure IDs. |
| `8/4` | Category / section row | **Fragment** — one forum row in the section list (`$FORUM_TITLE$`, counts, last post). |
| `8/5` | Topic / poll row | **Fragment** — one topic row in a forum’s topic list (`$THREAD_TITLE$`, posts, views, last post). |

There is **no** dedicated forum CSS template (unlike shop `20/9` or photo `9/10`). Style via site CSS `module 3 / template 3`, scoped to `body.module-forum`.

---

## Body / module keyword

`<body class="module-$MODULE_ID$">` → **`body.module-forum`**.

Always scope forum CSS under `body.module-forum` so global theme rules are not polluted.

---

## Architecture (how pages assemble)

1. **Shell `8/1`** always renders. Inside `.forumContent`: quick nav (PM, recent threads, members, rules, search, RSS) + `$BODY$`.
2. **`$BODY$` content depends on page type** (index of forums, topic list, thread view, search, members, rules, etc.). Row markup for lists comes from `8/4` / `8/5`; posts in a thread come from `8/2`; compose UI from `8/3`.
3. Edit the **fragment** that owns the element — not only the shell — same idea as Entry view in content modules, but with forum-specific template IDs.

---

## Hard rules

### Do not invent variables

Confirm via `get_variables` / `read_template` on the **exact** template. Forum has many specialized links (`$RECENT_THREADS_LINK$`, `$BOARD_RULES_LINK$`, `$LAST_POST_LINK$`, …). Unknown `$VAR$` prints as literal text.

### Preserve language markers

Keep `<!--<sNNNN>-->…<!--</s>-->` intact when patching nav labels and system strings.

### Do not restructure the compose form (`8/3`)

The form uses a `<table class="gTable" id="frM53">` with many fixed `id="frM*"`, `id="frF*"`, and option field IDs (`pollonly`, `ahtml`, …). Platform JS and preview (`prepost`, `postPreview`) depend on this shape.

Allowed:

- Wrapper divs **around** the whole form block
- CSS on existing classes (`gTable`, `gTableLeft`, `gTableRight`, `.postSubmit`, …)

Forbidden:

- Renaming/removing `id`s
- Flattening the table into arbitrary flex markup that drops those IDs
- Changing submit control types/classes without checking JS (`.postSubmit`, `.postPreview`, `.postReset`)

### Compose form spacing (`#frM53`)

Theme `2301` uses `grid-template-columns:1fr 4fr`, `align-items:center`, and large `gap` on `#frM53` rows — that creates huge empty space between labels and checkboxes.

Override under `body.module-forum`:

- `tbody` gap ~14px (not theme `xl`)
- field rows: `grid-template-columns: minmax(132px,180px) minmax(0,1fr)` + `align-items:start`
- `#frM56` (message): single column, label above editor
- `#frM8` / `#frM16`: checkbox grid `auto 1fr`, hide leftover `<br>`
- `#frM52` uploader: pill links (hide `[ | ]` text nodes via `font-size:0` on cell), full-width dropzone
- `.postReset` secondary pill; `.postPreview` / `.postSubmit` primary

### Tables are structural, then restyled

Theme CSS often turns forum tables into CSS grid (`display:block` / `display:grid` on `table/tbody/tr`). When restyling:

- Prefer CSS on existing classes (`.forum-section-table`, `.forum-topics-table`, `.forum-cat-item`, `.topic-item-*`, `.post-card`)
- Re-check **index, topic list, and thread view** after any grid change — layouts differ
- On small screens, theme already collapses columns; keep overrides in matching breakpoints

### `$BODY$` is not always a list of `8/2`

On the forum home, `$BODY$` is the section/forum listing (rows from `8/4`). Inside a thread, `$BODY$` is posts (`8/2`) plus chrome. Always `read_template` + fetch the live page before assuming structure.

### `$POWERED_BY$`

Shell `8/1` is a full page — keep copyright visible via `$GLOBAL_BFOOTER$` (or shell), never hide it.

### `backdrop-filter` caution

Glass styles use `backdrop-filter`. Do **not** nest `position:fixed` overlays (mobile menu, modals) inside an element that has `backdrop-filter` — they clip to that box. Keep glass on `.forumContent` / cards, not on the site header that contains the mobile menu. See [ARCHITECTURE.md](ARCHITECTURE.md).

---

## Useful shell variables (`8/1`)

| Variable | Role |
|---|---|
| `$BODY$` | Main forum content for the current view |
| `$PM_URL$` / `$UNREAD_PM$` / `$IS_NEW_PM$` | Private messages |
| `$RECENT_THREADS_LINK$` | Updated threads |
| `$RECENT_POSTS_BY_MSG_LINK$` | New messages |
| `$MEMBERS_LIST_LINK$` | Members |
| `$BOARD_RULES_LINK$` | Rules |
| `$SEARCH_PAGE_LINK$` | Search |
| `$RSS_LINK$` | RSS |
| `$SUBSCRIPTION_LINK$` | Subscriptions (when logged in) |
| `$CUR_*` | Current section/forum/thread names & URLs (breadcrumbs / titles) |

---

## Post fragment (`8/2`) — keep dynamic chrome

Preserve when present: `$ENTRY_MANAGE_ICON_BAR$`, `$USER_DETAILS_ICON_BAR$`, `$DEL_ENTRY_FIELD$`, `$ON_TOP_ICON$`, awards/reputation/ban URLs, `$ATTACHMENT$`, `$SIGNATURE$`, `$EDITEDBY$`. Restyle with CSS; do not drop moderation/tooling markup.

---

## Thread page chrome (important)

On topic pages (`threadpage-layout`) platform HTML includes tables/classes that are **not** in `8/1`–`8/5` fragments — they come from the engine around `$BODY$`:

| Class / area | Role |
|---|---|
| `.ThrTopButtonsTbl` / `.frmBtns` | ADD REPLY / NEW TOPIC / NEW POLL |
| `.forum-pages` / `.switches` | Pagination |
| `.ThrForumBarTbl` / `.breadcrumbs` / `.forumNamesBar` | Breadcrumb path + subscribe |
| `.threadpage-posts-table` / `.forum-title` | Topic title row |
| `.ThrBotTbl` | Bottom path, search-in-topic, fast nav |

A redesign that only styles `.forumContent` + `.post-card` still looks “stock”. **Always override these chrome classes** under `body.module-forum` with high-specificity / `!important` rules — theme `2301` CSS is aggressive.

### Top tools nav: no bracket links

Do **not** keep the legacy `[ link · link · link ]` pattern (`.forum-bracket-symbol` / `.forum-middot-symbol`).

Replace the shell menu with a flex `<nav class="forum-menu forum-menu-pills">` of `.fNavLink` pill buttons. Hide any leftover bracket/middot symbols with CSS.

### Stock action buttons are SVG images

Reply / New topic / New poll / Profile / Quote / etc. often render as
`<a><img src="/.s/img/fr/bt/…/*.svg"></a>`, not text. Restyling only `a` leaves the old bitmaps.

Replace with CSS text pills:

- hide `img` (visually / accessibility-safe clip)
- `a:has(img[src*="t_reply"])::after { content: "Ответить"; }` (and `t_new`, `t_poll`, `p_profile`, `p_quote`, `p_edit`, `p_del`, `p_up`)

Do this in the same forum stylesheet as the glass rules.

### Thread toolbar layout

On topic pages, `.ThrForumBarCl12` often holds **in-thread search** (`#thread_search_form`), not only subscribe. Do not style that cell as a single pill link.

Theme `2301` sets `.ThrForumBarRow1` / `.ThrBotRow3` to `flex-direction:column`. Always override with `flex-direction:row !important` or chrome stacks vertically and looks broken.

Use horizontal flex rows:

1. pagination | reply actions  
2. breadcrumbs | (optional tools)  
3. search + moderator options  
4. title | `#subscribe` pill  

`#subscribe` is an engine **wrapper div** around `.thread_subscribe`, with literal `[ ` / ` ]` text nodes — not the link itself. Style the inner `a`; hide brackets with `font-size:0` on `#subscribe` and restore size on the link. Do not set `font-size` on `#subscribe` itself or the brackets reappear.

### Full-width posts

When turning forum tables into blocks, use **`width:100%`**, never `width:auto`, on `.threadpage-posts-table` / post `td` / `.post-card`. `width:auto` shrinks the card to content.

### Duplicates under messages

Engine outputs thread chrome **twice**: top (`ThrForumBarTbl`) and bottom (`ThrBotTbl` → `.ThrBotRow1` / `.bottomPathBar` with another breadcrumbs + `#thread_search_form`).

Hiding with only `.ThrBotRow1{display:none}` often **fails**: a broader rule like `.ThrBotTbl tr{display:block!important}` has higher specificity and wins. Use:

`body.module-forum .ThrBotTbl tr.ThrBotRow1` / `.ThrBotTbl .ThrForumBarTbl` / `.ThrBotTbl #thread_search_form { display:none !important }`

Also:

- `$ENTRY_MANAGE_ICON_BAR$` must appear **once** (footer `.post-buttons`), not also in `.post-manage-bar` in the sidebar.  
- Do not output `$AJAX_JS$` twice in `8/1`.

### Beat theme defaults

Forum restyles usually lose to `3/3` / theme rules. Practical approach:

1. Put a dense `body.module-forum … !important` block in shell `8/1` (or a dedicated FTP CSS linked only from `8/1`) so it wins on forum pages.
2. After each save: fetch `/forum` **and** a topic URL like `/forum/3-1-1` — index and thread chrome differ.
3. If `patch_template` on `3/3` reports success but does not persist, use `update_template` on `8/1` (full shell) rather than retrying blindly.

Full page shells that include `</body>` **must** contain `$POWERED_BY$` (after `</body>` is accepted by the API).

---

## Styling strategy

1. Prefer **CSS-only** redesign in `3/3` under `body.module-forum`, or one FTP asset linked from `8/1`.
2. If `patch_template` on `3/3` reports success but `read_template` shows no change, recover with `update_template` on `3/3`, or temporarily put a scoped `<style id="forum-…">` in `8/1` (forum-only shell) and migrate later.
3. Change fragment HTML (`8/2`, `8/4`, `8/5`) only when structure must change — keep variables and conditionals.
4. Never “clean up” `8/3` IDs for aesthetics.
5. After CSS edits, re-`read_template` and fetch the live `/forum` page to confirm persistence.

---

## Related

- [SYSTEM-MARKUP.md](SYSTEM-MARKUP.md) — `gTable` / form styling parallels
- [UWND.md](UWND.md) — site-wide `_uWnd` / `xw-*` toasts and modals (not forum-specific)
- [ARCHITECTURE.md](ARCHITECTURE.md) — CSS placement, `backdrop-filter` trap
- [BLOG.md](BLOG.md) — content modules (different chain; do not confuse with forum)
- [../EXAMPLES.md](../EXAMPLES.md#forum-glass-apple) — glass Apple CSS pattern used on demo
- [../EXAMPLES.md](../EXAMPLES.md#uwnd-glass) — `_uWnd` CSS sample

# Configure access to uCoz sites

The skill never requests or configures credentials. Access comes from the user signing in to their uCoz account through uCoz MCP.

## Connection

uCoz MCP connects at `https://www.ucoz.com/mcp` — the client config needs only the URL, no tokens:

```json
{"mcpServers":{"ucoz-mcp":{"url":"https://www.ucoz.com/mcp"}}}
```

On first connection the agent opens a sign-in window: the user signs in to their uCoz account and clicks “Allow”. Then `list_sites` → `select_site(site_id)`; all site tools work on the selected site. One connection sees every site in the account.

Operations that need administrator rights (templates, menus, pages, modules) work only if the signed-in account owns the site or has administrator rights on it.

## In-place translation (one site)

A single `select_site(site_id)` for the site being translated is enough.

## Cross-site migration (two sites)

Both sites must be in the same uCoz account. If they are not, the user runs the stages sequentially: read the source, then sign in to the target account and write.

1. `select_site(source)` → read and inventory; the source is read-only.
2. `select_site(target)` → write.
3. Before every write, check the host in the tool response against the target site; stop on any mismatch.

Static template/global-block files move through `files_read` / `files_write` on the selected site. Text files: from the source `files_read(action="content_get", path="<file path>")`, then after `select_site(target)` — `files_write(action="content_put", path="<file path>", content=..., create=true)` (create the parent folder with `mkdir` if needed). Binary files: `files_write` `upload_direct` for each file (`filename`, exact `size`, raw POST to `upload_url`), then one `files_write(action="upload", path="<folder>", upload_ids="...")` (at most 20). `upload_url` is only when the source file is already at a public http(s) address.

## Verification

Before any write, make a read-only call on each site (for example `modules_read.active_mods` or reading one material through `content_read` / `content_write`) and check the host in the response against the expected site.

## File tool fallback

Static template and global-block files go through `files_write`.

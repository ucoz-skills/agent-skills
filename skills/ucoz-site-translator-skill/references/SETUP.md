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

Static template/global-block files move through `files_tool` on the selected site. Text files: from the source `files_tool(action="content_get", path="<file path>")`, then after `select_site(target)` — `files_tool(action="content_put", path="<file path>", content=..., create=true)` (create the parent folder with `mkdir` if needed). Binary files: on the target `files_tool(action="upload_url", path="<folder>", url="<public URL of the source file>")` or `upload` with `content_base64`.

## Verification

Before any write, make a read-only call on each site (for example `modules_tool.active_mods` or reading one material through `content_tool`) and check the host in the response against the expected site.

## Legacy NPM MCP (fallback)

If a local NPM MCP is connected, it works with one site per connection: migration needs two named connections (source and target). Files go through `ftp_tool` (`list`/`read`/`write`). The user configures credentials in the client config outside the chat.

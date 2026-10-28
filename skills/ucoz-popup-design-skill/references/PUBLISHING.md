# Publishing through the official MCP

Publish only after the user approved the look (or the request itself said to apply it).

## Steps

1. **Site.** Check that the host returned by `select_site` is the user's domain.
2. **Templates.** `templates_read(action="list_modules")` → numeric `module_id` / `template_id`. Read each candidate with `templates_read(action="read_template")` and save the originals locally, outside the skill folder.
3. **Insertion point.** Find where the link loads on every page after the system CSS. Keep template variables, conditions, `$AJAX_JS$`, `$POWERED_BY$` and functional form fields.
4. **Backup.** Read `templates_read(action="get_backup_guide")`. Every `patch_template` makes an automatic, rotated backup and returns `backup.id` — record it. For a backup that is never rotated, call `templates_write(action="create_backup")` first. If it is refused with `LIMIT_REACHED`, rely on the automatic backup and tell the user. Check the id with `templates_read(action="list_backups")`.
5. **CSS file.** `files_read(action="quota")` and `files_read(action="list", path="<folder>")`. Create the folder with `files_write(action="mkdir")` if needed. Write the file:

   ```text
   files_write(action="content_put", path="<folder>/uwnd-design-<version>.css", content=<css>, create=true)
   ```

   Read it back with `files_read(action="content_get")` and compare it byte for byte. Paths have no leading or trailing slash; the link uses `/<folder>/uwnd-design-<version>.css`. Never overwrite a file you did not create.
6. **Link.** The insertion is exactly:

   ```html
   <!-- uwnd-design:start v<version> -->
   <link rel="stylesheet" href="/<folder>/uwnd-design-<version>.css">
   <!-- uwnd-design:end -->
   ```

   Check the patch with `templates_read(action="validate_template", module_id, template_id, code_search, code_paste)`, then apply it with `templates_write(action="patch_template", ...)` using a unique `code_search` anchor. If markers already exist, the `code_search` is the whole marked block exactly as `read_template` shows it — from `<!-- uwnd-design:start` (earlier installs may carry another version or file name, such as `<!-- uwnd-design:start v0.2.0 -->` with `/uwnd-design/uwnd-0.2.0-light.css`) through `<!-- uwnd-design:end -->` — and the replacement carries the new version and link.
7. **Read-back.** `read_template` again and compare with the expected result exactly.
8. **Browser check.** → `VERIFICATION.md`

## HTTP 413 (request too large)

First check whether anything was written before retrying. Split the CSS only at complete top-level rules and keep the order (several files linked in sequence inside the same marked block). Never split inside a media block, a string or a comment. The file quota and the request size limit are different limits.

## Rollback

1. `read_template` the current template — other edits may have happened since.
2. `patch_template` with `code_search` = the whole marked block (start marker to end marker) and `code_paste` = `""`. Later edits stay.
3. Delete the CSS file with `files_delete` only after the user approves it.

Never restore a whole old template backup or the whole site just to remove one stylesheet link.

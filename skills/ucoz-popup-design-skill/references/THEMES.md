# Themes and palettes

## Three ways to choose

| Option | What to do |
|---|---|
| Preset | Light, Dark, Lavender, Mint or Peach from `assets/themes.json` |
| Match the site | Read the live page outside the popups: backgrounds, fonts, link and button colours, radii, light/dark selectors. Map them to the tokens below |
| Custom | Ask only for what is missing: colours, a reference, density, rounding |

## Tokens

The base stylesheet `assets/uwnd-overrides.css` is driven by `--uwd-*` custom properties.

| Token | Meaning |
|---|---|
| `--uwd-surface` | Window background |
| `--uwd-soft` | Secondary background (table rows, disabled fields) |
| `--uwd-text` | Main text |
| `--uwd-muted` | Secondary text, toast body, window control icons |
| `--uwd-border` | Borders and separators |
| `--uwd-accent` | Links, primary buttons, focus |
| `--uwd-hover` | Hover of links and primary buttons |
| `--uwd-on-accent` | Text on accent backgrounds |
| `--uwd-field` | Field background |
| `--uwd-danger`, `--uwd-success`, `--uwd-warning` | Feedback states |
| `--uwd-shadow` | Window shadow |
| `--uwd-scheme` | `light` or `dark` (`color-scheme`) |

The stylesheet also defines `--uwd-font` and `--uwd-radius`. Bind tokens to site variables (for example `--uwd-accent: var(--site-accent)`) only after seeing those variable names on the live site. Keep the site's real light/dark behaviour: if the site switches themes with a class or attribute, give each mode its own token values under that selector.

## Theme builder

```bash
python scripts/build_theme.py --theme mint --output uwnd-theme.css
```

- `--theme`: `light` (default), `dark`, `lavender`, `mint`, `peach`.
- `--accent #RRGGBB`: custom accent; the text colour on it is chosen automatically. The accent is also the link colour.
- `--hover #RRGGBB`: hover colour (defaults to the custom accent).
- `--output`: required.

The builder reads the base stylesheet and the preset, appends the selected palette as `:root` tokens, and checks contrast for text, muted, accent, danger, success and warning on the surface, and on-accent text on accent and hover. Any pair below 4.5:1 stops the build with an error. It makes no network calls and writes nothing to the site.

For a site-matched or custom palette edited by hand, check the same pairs yourself before publishing.

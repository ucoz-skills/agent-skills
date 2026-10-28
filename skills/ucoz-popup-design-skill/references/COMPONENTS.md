# Native popup components

Inspect the current DOM and loaded resources on the live site first: versions may differ from this map.

## Where the code comes from

| Resource | Usual path |
|---|---|
| Base styles | `/.s/src/base.min.css` |
| Layer tokens (toasts) | `/.s/src/layer7.min.css` |
| Window library | `/.s/src/uwnd.min.js` |

These are system files. Read them, never edit them; override from your own stylesheet loaded after them.

## Ordinary windows

| Part | Selector | Notes |
|---|---|---|
| Root | `[id^="_uwndTop"]` | Fixed-position wrapper; may have zero width and height |
| Visible frame | `.xw-plain` | |
| Header text | `.xw-hdr-text` | |
| Body | `.myWinCont` | |
| Frame backgrounds | `.xw-tl`, `.xw-ml`, `.xw-bl`, `.xw-blank` | 9-slice parts |
| Window controls | `.xt-close`, `.xt-mini`, `.xt-maxi`, `.xt-rest` | Keep geometry and display state |
| Shadow | `.xw-shadow` | Also used by menus: never restyle globally |

Exclude from the ordinary-window scope: lightbox (`.x-lb`), Control Panel dialogs (`.cp-modal`, `.cp-alert`) and toasts (`.uwnd-toast`). The scope selector:

```css
body [id^="_uwndTop"]:not(.x-lb,.cp-modal,.cp-alert,.uwnd-toast)
```

## Content inside windows

| Element | Selectors |
|---|---|
| Fields | native `input`, `select`, `textarea`, with `label`; states `checked`, `indeterminate`, `readonly`, `disabled` |
| Feedback | `.u-form-error`, `.myWinError`, `.myWinSuccess` |
| Buttons | `.ubtn-default`, `.light-btn`, `.red-btn` |
| Confirmation | `.messagebox-content`, `.messagebox-actions` |

Styling rules:

- Style text, headings, links, fields, select, checkbox/radio, buttons, close icons and feedback states.
- Keep native input semantics and keyboard behaviour.
- Focus outlines are inset so `overflow` wrappers do not clip them.
- Reserve space for one select arrow; leave multiple selects alone.
- Never set `width:100%` on every field.
- Preserve inline dimensions, hidden controls, drag/resize regions and handlers.

## Toasts

| Part | Selector |
|---|---|
| Toast root | `.uwnd-toast` |
| Measurement box | `.uwnd-toast-measure` with `.alert-measure-title` / `.alert-measure-message` |

The measurement box must use the same typography and box sizing as the visible toast, otherwise long toasts get the wrong height. Keep the semantic SVG masks of the toast icons. Layer tokens (`--layer-*`) may be overridden only inside `.uwnd-toast`.

## JavaScript API (for demos only)

| Call | Notes |
|---|---|
| `_uWnd.messageBox(message, title, buttons, callback, options)` | Supports `autosize`; use it for tests — fixed 100px content can clip larger buttons |
| `_uWnd.alert(message, title, options)` | |
| `uToast.show(title, message, options)` | The first two arguments are in the opposite order to `_uWnd.alert` |

Demos use the site's own library, sample text and fields, and local callbacks. Store the instances you open and close only those. Never close a window name you did not create. For production calls change only an authorized template when needed.

## Audit script

`scripts/inspect-windows.js` is a function for a browser `evaluate`. Open representative windows first. It returns the page URL, viewport, loaded stylesheets and scripts, site CSS variables (font, accent, colour, radius, surface) and, for each window, the classes, roles, tabindex, computed styles and geometry of its nodes. It does not read form values, cookies, tokens or library internals.

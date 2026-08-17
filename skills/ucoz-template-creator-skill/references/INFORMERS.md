# Informers

## Contents

- Creation lifecycle
- JSON contract
- Tested examples
- Layout variants
- Placement and QA

## Creation lifecycle

`new_informer` creates an informer; it does not place it. Multiple definitions are allowed. Creation requires two framework publications:

1. Publish definitions and capture `created_informers`.
2. Remove all definitions, insert returned `$MYINF_N$` codes at intentional locations, validate, save, and publish again.

If definitions remain, uCoz may append informer codes after `</html>` in every generated template. Informers remain available after definitions are removed.

Interpret the result literally:

- `created: 1` — created during this publication;
- `created: 0` — an existing informer was reused;
- `code` — placement variable such as `$MYINF_2$`.

## JSON contract

Use valid JSON inside exact `new_informer` markers. Typical keys are `title`, `module`, `sort`, `max_entries`, `max_columns`, `categories_list`, `entries_list`, `title_max_length`, `data_type`, `curdate`, `template`, and `no_entries_msg`.

Do not guess `sort`, `data_type`, or `curdate`; use a tested informer or control-panel configuration. Variables in `template` depend on `module`. Common module codes include `nw`, `bl`, `pu`, `ld`, `dr`, `bd`, `ph`, `fr`, `sf`, `sh`, and `vi`.

The News and Publ definitions below were installed successfully on the test site. Shop fields vary by configuration and must be retrieved from the live guide or a compatible standard design before use.

### Tested News informer

```html
<!--<new_informer>-->
{
  "title":"Свежее в новостях",
  "module":"nw",
  "sort":"1",
  "max_entries":"5",
  "max_columns":"1",
  "categories_list":"",
  "entries_list":"",
  "title_max_length":"72",
  "data_type":"0",
  "curdate":"0",
  "template":"<a class=\"feed-line\" href=\"$ENTRY_URL$\"><span class=\"feed-line__title\">$TITLE$</span></a>",
  "no_entries_msg":"Новых материалов пока нет"
}
<!--</new_informer>-->
```

### Tested Publ informer with different markup

```html
<!--<new_informer>-->
{
  "title":"Новые статьи",
  "module":"pu",
  "sort":"1",
  "max_entries":"4",
  "max_columns":"1",
  "categories_list":"",
  "entries_list":"",
  "title_max_length":"72",
  "data_type":"0",
  "curdate":"0",
  "template":"<article class=\"article-note\"><span class=\"article-note__eyebrow\">Каталог статей</span><a class=\"article-note__title\" href=\"$ENTRY_URL$\">$TITLE$</a></article>",
  "no_entries_msg":"Статей пока нет"
}
<!--</new_informer>-->
```

These examples intentionally differ structurally. Do not force all informers into one card pattern.

## Layout variants

- **News pulse:** use the tested News contract with a `pulse-line` anchor, status dot, and title in a narrow rail or beneath Search.
- **News signal strip:** use short `signal-chip` anchors in a wrapping or horizontally scrolling band. Preserve keyboard focus and reduced-motion behavior.
- **Publ reading index:** use wide editorial rows with a section kicker, prominent title, thin rule, and one semantic arrow.
- **Publ dossier:** use bordered research notes in two columns only where width permits, collapsing to one below roughly 760 px.
- **Shop product grid:** retrieve the live Shop template and preserve native image, price/options, `$2BASKET$`, `$2COMPARE$`, and `$2WISHLIST$` behavior. Supporting icons must not replace native controls.

Use a unique title, verify that the module is installed, confirm every variable, and test empty state, long titles, keyboard focus, and 320 px layout. Informers in one theme should have intentionally different hierarchy and geometry when their roles differ.

## Placement and QA

Place `$MYINF_N$` inside a named section with a heading and empty-state-safe layout. Choose locations by content intent: editorial homepage, category rail, related reading after an entry, product grid, or compact footer index. Do not add a generic latest-content rail to every page.

After pass one, confirm the informer exists in the control panel and record `title`, `id`, `created`, and `code`. After pass two, confirm every code appears exactly where intended, no informer tail exists after `</html>`, and no `new_informer` definition remains in the published source.

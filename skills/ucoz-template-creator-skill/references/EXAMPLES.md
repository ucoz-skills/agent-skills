# Varied implementation patterns

These are optional patterns, not a house style. Choose by content and rewrite names, copy, spacing, and hierarchy. Platform rules in the other references override examples.

## Contents

- Complete framework compositions
- Editorial lead story
- Brutalist archive row
- Warm article card
- Technical status card
- Off-canvas header
- Footer compositions
- Popup shell

## Complete framework compositions

Treat a framework as one visual system, not a pile of interchangeable cards. The bundled `examples/` starters are complete enough to validate, but still require live `skeleton_guide`, `skeleton_rules`, source-design comparison and target-module variable checks before publication.

### Editorial journal

- Header: compact seal, wordmark, centered navigation, search/account at the edge.
- Middle: optional issue intro, asymmetric reading column plus editorial rail.
- Views: image-led News, handwritten Blog rhythm, indexed Publ rows.
- Informers: compact News pulse in the rail and a wide Publ reading index after full articles.
- Footer: manifesto, repeated navigation and `$POWERED_BY$`; no invented social links.
- Rating: verified `<?$RSTARS$(...)?>` on full material pages, driven by a warm custom three-state sprite; `$RATING$` is reserved for its availability condition or numeric list metadata.

See [editorial-journal-framework.html](../examples/editorial-journal-framework.html) and [editorial-journal.css](../examples/editorial-journal.css).

### Technical catalog

- Header: dense utility bar, status marker, search-first navigation.
- Middle: wide results region with a filter/container column.
- Views: strict rows, module identity icons, monospace metadata and high-contrast actions.
- Informers: horizontal signal strip, not a soft editorial card.
- Footer: compact sitemap and system/version line.
- Rating: sharp geometric sprite; Shop keeps native basket/options/compare behavior.

See [technical-catalog-framework.html](../examples/technical-catalog-framework.html) and [technical-catalog.css](../examples/technical-catalog.css).

### Compact community

- Header: friendly wordmark and prominent account state.
- Middle: single reading column with forum/guestbook side activity only on wide screens.
- Views: conversation-first Forum and Guestbook blocks, clear author/time, native moderation intact.
- Informers: a short activity list with visible empty state.
- Footer: community navigation and `$POWERED_BY$`; social links only when real destinations are provided.
- Rating: Guestbook uses its native like/dislike URLs; vote-capable full material pages keep the verified `<?$RSTARS$(...)?>` generator.

See [compact-community-framework.html](../examples/compact-community-framework.html) and [compact-community.css](../examples/compact-community.css). Its forum blocks must be copied from the retrieved live source before customization because state variables and controls are unusually dense.

### Gallery atelier

- Header: quiet wordmark and restrained off-canvas navigation.
- Middle: oversized exhibition title, wide art surface and a small current-selection index.
- Views: Photo is treated as artwork with a separate caption column, views/comments and native rating.
- Containers: gallery directory and collector/account panel.
- Rating: mineral normal state, terracotta hover and deep green selection.

See [gallery-atelier-framework.html](../examples/gallery-atelier-framework.html) and [gallery-atelier.css](../examples/gallery-atelier.css).

### Brutalist bulletin

- Header: boxed masthead, issue stamp and high-contrast drawer.
- Middle: yellow marquee, hard black rules and an intentionally loud wire block.
- Views: monochrome News image, uppercase report hierarchy and block action.
- Containers: directory and access panels; no soft cards or gradients.
- Rating: pale normal state, signal-yellow hover and red selection through the native generator.

See [brutalist-bulletin-framework.html](../examples/brutalist-bulletin-framework.html) and [brutalist-bulletin.css](../examples/brutalist-bulletin.css).

All five framework files keep CSS outside the skeleton and point to `/_st/my.css?v=1`. A saved tmaker project stores the framework source, not its companion CSS file. Before publishing a chosen example, upload its matching CSS as the complete `3/3` template and run the normal backup/validation workflow.

## Editorial lead story

Use an image-led composition with the category overlaid on media and text below it.

```html
<article class="lead-story">
  <a class="lead-story__media" href="$ENTRY_URL$">
    <?if($COVER_IMAGE$)?><img src="$COVER_URL$" alt="$TITLE$" loading="lazy"><?endif?>
    <span class="lead-story__category">$CATEGORY_NAME$</span>
  </a>
  <div class="lead-story__copy">
    <p class="lead-story__kicker">Selected story</p>
    <h2><a href="$ENTRY_URL$">$TITLE$</a></h2>
    <div class="lead-story__summary">$MESSAGE$</div>
  </div>
</article>
```

Use only variables confirmed for the module. The visual distinction comes from the media/copy hierarchy, not from adding a gradient pill to everything.

## Brutalist archive row

```html
<article class="index-row">
  <span class="index-row__number" aria-hidden="true">$ID$</span>
  <h2 class="index-row__title"><a href="$ENTRY_URL$">$TITLE$</a></h2>
  <div class="index-row__meta">$DATE$</div>
  <a class="index-row__arrow" href="$ENTRY_URL$" aria-label="Открыть материал">
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M14 7l5 5-5 5"/></svg>
  </a>
</article>
```

Style with hard borders, a visible grid, and one signal color. This pattern deliberately has no card background or image.

## Warm article card

```html
<article class="journal-card">
  <?if($COVER_IMAGE$)?><a class="journal-card__photo" href="$ENTRY_URL$"><img src="$COVER_URL$" alt="$TITLE$" loading="lazy"></a><?endif?>
  <div class="journal-card__body">
    <span class="journal-card__label">Journal</span>
    <h2><a href="$ENTRY_URL$">$TITLE$</a></h2>
    <div class="journal-card__summary">$MESSAGE$</div>
    <div class="journal-card__rule" aria-hidden="true"></div>
    <div class="journal-card__meta">$DATE$ · $USERNAME$</div>
  </div>
</article>
```

Use a warm paper surface, serif title, irregular image crop, and soft but visible separators.

## Technical status card

```html
<article class="signal-card">
  <header class="signal-card__top">
    <span class="signal-card__status"><i aria-hidden="true"></i> Online</span>
    <span class="signal-card__date">$DATE$</span>
  </header>
  <h2><a href="$ENTRY_URL$">$TITLE$</a></h2>
  <div class="signal-card__summary">$MESSAGE$</div>
  <footer class="signal-card__bottom">
    <span>$READS$ views</span>
    <span class="signal-card__rating">$RATING$</span>
  </footer>
</article>
```

Use mono labels, a compact information grid, and strict state colors. Do not reuse this density for a cultural magazine.

## Off-canvas header

Use one accessible checkbox state when the framework must work without custom JavaScript:

```html
<header class="site-header">
  <div class="site-header__bar">
    <a class="site-brand" href="$HOME_PAGE_LINK$">$SITE_NAME$</a>
    <input class="nav-toggle" id="nav-toggle" type="checkbox">
    <label class="nav-open" for="nav-toggle" aria-label="Открыть меню">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
    </label>
    <div class="nav-drawer">
      <label class="nav-close" for="nav-toggle" aria-label="Закрыть меню">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg>
      </label>
      <nav aria-label="Основная навигация">$NMENU_1$</nav>
      <div class="nav-drawer__account">$LOG_FORM$</div>
    </div>
    <label class="nav-scrim" for="nav-toggle" aria-hidden="true"></label>
  </div>
</header>
```

Do not use `display:none` on the checkbox; visually hide it while preserving keyboard access. The drawer and scrim use `position:fixed` and must not be descendants of transformed/filtered containers.

## Footer compositions

Choose one rather than combining all:

- Editorial: large site name, one sentence, three compact columns, thin baseline.
- Brutalist: numbered navigation rows and a high-contrast legal strip.
- Warm: short manifesto, contact details, quiet secondary navigation.
- Technical: system status, compact sitemap, monospace version/year line.
- Swiss: oversized wordmark crossing the grid with small links aligned below.

Keep one visible `$POWERED_BY$`. Do not invent social links when the user supplied none.

## Popup shell

```html
<!-- <popup> -->
<section class="popup-shell" role="dialog" aria-modal="true" aria-labelledby="popup-title">
  <header class="popup-shell__header"><h1 id="popup-title">[TITLE]</h1></header>
  <div class="popup-shell__body">[BODY]</div>
</section>
<!-- </popup> -->
```

Preserve `[TITLE]` and `[BODY]`. Style native `_uWnd` wrappers in `3/3` after inspecting live DOM; do not assume this inner fragment replaces the whole platform window.

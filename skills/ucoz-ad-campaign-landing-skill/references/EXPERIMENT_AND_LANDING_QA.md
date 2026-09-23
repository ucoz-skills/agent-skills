# Hyper-Segmentation Matrix and Landing QA

Read this before generating a landing series and before publishing the final variants.

## 1. Hypothesis first, then pages

Each variant must change one factor named in advance. Do not create “different pretty pages” without a measurable hypothesis.

Base matrix for a website-builder product:

| Factor | Values | What we test |
|---|---|---|
| Price visibility | shown / hidden | whether a specific price affects quality and conversion |
| CTA direction | signup / thematic_templates / all_templates | where the user starts creating a site more easily |
| Niche | legal / construction / beauty / psychology / other | message match and demand quality |

Combining the first two factors produces six pages per niche. Each cell needs its own slug, utm_content, landing_url, and campaign_id.

Do not mix an extra hypothesis into the same cell, for example AI site transfer, unless the user set it aside as a separate test.

## 2. Public copy and the internal name

The internal name can be technical:

    legal / hidden / all_templates

The user must not see “variant without a price”, “all templates”, or “we are testing conversion”. Public copy explains the benefit:

- “Choose a suitable design”;
- “Start from a ready-made base”;
- “Create a site for your practice”.

If the only difference is the destination link, do not rewrite the H1 for the sake of a technical label.

## 3. Brand layer

Source priority:

1. the logo and brand materials specified by the user;
2. the live product site;
3. the corporate site or the design system;
4. the existing icon set;
5. only then — neutral choices by the agent.

Do not approximate the brand when the user provided an exact logo, a Figma file, or an SVG set.

### Icons

- Use one set on every card and every page in the series.
- Do not mix outline and filled styles.
- Do not draw random icons when the set already has a suitable metaphor.
- Check that stroke, size, optical weight, and color match.
- Do not use emoji as UI icons.

### 24/7 support

If the product really provides 24/7 support, show it as a standalone advantage and repeat it next to the deciding CTA. Do not turn the advantage into a technical footnote.

### Fixed header

A fixed header is allowed if it:

- does not cover the hero or anchor headings;
- takes a reasonable height on mobile;
- keeps access to the primary CTA;
- works with anchors through scroll-margin-top.

## 4. Price

For the shown variant, the same price value must be consistent in the ad, the hero, the pricing block, and the FAQ.

For the hidden variant:

- do not show a specific price in the hero, on cards, or in service captions;
- do not write “price hidden”;
- do not leave the number in meta tags or in the ad text;
- a neutral path to pricing is allowed when it is part of the agreed scenario.

## 5. CTAs and destination links

The CTA must match the real action:

| Scenario | Allowed CTA |
|---|---|
| Sign-up | “Create a site”, “Try for free” |
| Thematic templates | “Choose a template”, “View designs” |
| General catalog | “Pick a design”, “Start with a template” |
| AI transfer | “Transfer the site with AI” — a separate test only |

Do not write “Call us” if the page has no phone scenario.

Every external CTA to sign-up, templates, pricing, and AI must receive UTM parameters. Minimum:

    utm_source
    utm_medium
    utm_campaign
    utm_content
    utm_term

Keep existing hash fragments. Do not drop UTM on a link such as /neowizard#templates/legal.

## 6. Example screenshots

If the user provides example sites:

1. open each URL and wait until the main content has loaded;
2. choose a consistent viewport height, and do not crop important text;
3. when the source site is fixed, take a new screenshot; do not use a cached one;
4. check the image before publishing;
5. store the source URL and the screenshot date in the manifest.

### Layout

- Use object-fit: contain, or the image’s natural aspect ratio.
- Do not use cover if it crops text or the interface.
- The card should adapt to the image, not the other way around.
- Screenshots of different heights may use cards of different heights.
- On mobile, the image uses the available width and stays readable.
- While screenshots are missing, use tidy placeholders without invented interfaces.

## 7. Technical copy

Remove from the public version:

- explanations of the CTA route;
- names of A/B hypotheses;
- “verified price” and similar internal wording;
- “registration will open at…”;
- promises of functionality that is not in the official sources.

## 8. Visual QA

Check 320, 375, 768, 1024, and a wide desktop.

At each size:

- there is no horizontal scroll;
- the header does not cover the H1 or the CTA;
- buttons do not break lines;
- icons share one style;
- screenshots are fully visible;
- long Russian text is not clipped;
- the price matches the variant;
- 24/7 support is visible and does not look pushy;
- every link opens the expected address with UTM;
- the Yandex Metrica counter is present once.

## 9. Series control

Compare the variants with each other programmatically or against the manifest:

- only the declared factors change;
- shared blocks, brand, and proof are the same;
- slug and utm_content are unique;
- destination_url matches the cell;
- public_copy does not contain internal_label;
- no page points to another niche’s URL.

## 10. Result

For each cell, record:

    segment_id
    hypothesis
    price_visibility
    destination
    page_id
    landing_url
    destination_url
    final_url
    screenshot_sources
    metrika_counter_id
    validation_status
    campaign_id
    package_strategy_id

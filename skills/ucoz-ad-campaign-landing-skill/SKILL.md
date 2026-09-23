---
name: ucoz-ad-campaign-landing-skill
version: "2.0"
description: >
  Designs hyper-segmentation, creates and publishes uCoz landing pages, prepares Yandex Direct search campaigns, groups them into package strategies, and links them to verified Yandex Metrica goals. Use for the chain "microsegment → ad → separate URL → campaign → shared budget package". Visual landing generation follows references/VISUAL.md (Landing Skill v1.4 contract) with the ad-series priorities from §0 of this file. Do not launch ads or change production without an explicit owner command.
---

# uCoz Landing + Yandex Direct Hypersegmentation

The skill takes an advertising experiment from a brief to a verifiable set of drafts: a hypothesis matrix, landing pages, ads, campaigns, package strategies, Yandex Metrica goals, and state evidence.

## Rule 0 — Usage accounting

After the skill's main work completes successfully, record usage:

```text
skills_tool(action="register_usage", skill_id="ucoz-ad-campaign-landing-skill")
```

If the usage API is unavailable or returns an error, show a brief warning and continue the skill's main workflow. Do not ask the user for a UUID, token, or site URL — the site and token come from the active `ucoz-mcp` connection.

## Fixed principles

1. First read the project AGENTS.md and local sources of facts. Do not carry IDs, goals, budgets, and product events across projects.
2. For each event, record separately:
   - product_event_status — whether the product sends the event;
   - metrika_goal_status — whether the goal exists and is reached;
   - direct_conversion_status — whether the conversion is available in Yandex Direct and selected by the strategy.
3. The presence of reachGoal does not prove that the goal exists in Yandex Metrica. The presence of a goal in Yandex Metrica does not prove import into or use in Yandex Direct.
4. Registration, publication, views, and clicks do not become quality goals automatically. For learning, a confirmed payment or another proven business result takes priority.
5. Before any external mutation, capture the read-only state and save the exact IDs, statuses, URLs, date, analysis window, and timezone.
6. Campaigns are created as drafts by default. Launch, moderation, top-up, deletion, changes to live strategies, and page publishing require an explicit user command; for deletion, confirmation immediately before the action.
7. The weekly budget of a package strategy is a shared pool. Do not describe it as the budget of each campaign.

## Routing

- Brief and overall sequence: references/INTAKE.md and references/PIPELINE.md.
- Hypothesis matrix, visual standard, and landing QA: references/EXPERIMENT_AND_LANDING_QA.md.
- Visual generation (style, grid, typography, tokens, icons, CSS/JS, motion, a11y): references/VISUAL.md — read it before the first line of CSS.
- Copy and message match: references/COPY.md.
- Publishing via uCoz MCP: references/LANDING_PLAYBOOK.md.
- Yandex Direct API: references/DIRECT_API.md and references/ADS.md.
- Work through the account UI, drafts, and package strategies: references/DIRECT_BROWSER_AND_PACKAGES.md.
- Yandex Metrica and goal selection: references/MEASUREMENT.md.
- Result handoff format: references/OUTPUT.md.
- Use Google Ads only on an explicit request; then read references/GOOGLE_ADS.md.

## Workflow

### 0. Audit before changes

Record:

- site, domain, existing pages, and the uCoz MCP connection;
- brand materials, current CSS/JS, and pages that must not be overwritten;
- the Yandex Metrica counter, available goals, and volumes for an explicitly stated window;
- the Yandex Direct account and organizational mode, existing campaigns, and package strategies;
- current budgets, launch statuses, and exact IDs.

Do not infer the state of one service from the state of another. Mark anything unknown as "requires verification".

### 1. Brief

Clarify only the decisions that are still missing:

- niche, geo, offer, and forbidden promises;
- brand, logo, SVG icon set, screenshots, or permission to create them;
- price test: with price / without price;
- target CTA destination: signup / thematic templates / general catalog / other;
- number of microsegments and the principle of matching the ad to the landing page;
- ad network and Yandex Direct access: API, browser, or preparation only;
- mode: drafts only, or an agreed launch;
- weekly budget of one package and the overall cap;
- counter, optimization candidate, and offline-conversion status.

If the answers are already known, do not ask again.

### 2. Experiment matrix

First create an explicit table of test cells. For a typical two-factor test:

    price_visibility: shown | hidden
    destination: signup | thematic_templates | all_templates

This yields six combinations per niche. Each combination gets its own segment_id, slug, UTM content, landing page, ad, and campaign. Internal names may contain a service distinction, but public copy must not reveal the technical mechanics of the test.

### 3. Research and copy

If ready-made ads do not exist, research demand by intent, and do not collect one shared keyword list. For each segment, prepare at least two ad variants, a single message match, and negative keywords.

Do not use the CTA "Call us" if the landing page has no phone or call action. Do not add technical phrases such as "verified price", "signup will open", "variant with all templates".

### 4. Landing pages

Follow references/LANDING_PLAYBOOK.md and references/EXPERIMENT_AND_LANDING_QA.md.

Before the first line of CSS or HTML, read references/VISUAL.md. One visual direction for the entire series (a shared style.css). If a general rule of the visual contract conflicts with hyper-segmentation, VISUAL.md §0 takes priority: message match and H1, a hero without a service catalog, contain for product screenshots, and the official logo over a monogram.

Mandatory practical rules:

- the style follows the brand references, not a random universal template;
- one SVG icon set on every page;
- 24/7 support must be a visible product advantage when the product confirms it;
- a fixed header is allowed if it does not cover content or anchors;
- show screenshots in full, without cropped text or interface;
- UTM must be preserved on every transition to signup, pricing, and templates, not only in the ad final URL;
- install the Yandex Metrica counter only with a confirmed ID, and verify it on the published URLs.

After page_add or page_update, run page_get, then validate_template if the action supports this template type. Do not skip validation silently: save the response or the exact reason it does not apply. Also check the public URL and responsiveness.

### 5. Yandex Direct campaigns

One test cell is one separate campaign, if the user approved that granularity. The campaign name must unambiguously encode the niche, the price, and the CTA destination.

After duplicating the source campaign, immediately check and, where needed, replace values at three levels:

1. campaign: name, promotion object, strategy, counter, goals;
2. ad group: name, geo, keywords, and negative keywords;
3. ad: H1, H2, text, CTA, final URL, and UTM.

Renaming the copy does not prove that the source URL and texts were replaced.

### 6. Package strategies

When every campaign of one niche has been checked, combine them into one package strategy only after the shared weekly budget is agreed. In the package name, state the niche, the number of landing pages, and the real shared budget.

After saving, reconcile:

- the package strategy ID;
- the exact list of campaign_id;
- the shared weekly budget;
- the strategy and the payment model;
- the counter and the selected goals;
- the status of each campaign — draft, if launch is not allowed.

Do not create parallel individual budgets that the user could take for extra spend.

### 7. Yandex Metrica and goals

Choose the goal after auditing business value, tracking reliability, volume, delay, and attribution. For offline and browser payment, separately check the source, time, deduplication, and coverage.

If a quality offline goal has been created but the backend does not send it, do not select it for learning as a working goal. A temporary browser goal may be used only with the limitation recorded explicitly and with a check of fresh volume.

### 8. Verification and handoff

Before the report, make sure that:

- every public URL opens;
- the H1 and the ad match each other;
- the price is consistent between the ad and the landing page;
- every CTA leads to the intended scenario and carries UTM;
- images are not cropped at 320/375/768/1024+;
- campaigns and package strategies have the expected IDs and budgets;
- nothing was launched or sent to moderation without a command;
- test duplicates are not mixed with live campaigns.

Save the manifest, read-only evidence from before and after the changes, and a short report following references/OUTPUT.md.

## Stop conditions

- The Yandex Direct organizational mode is visible in the browser, but the API returns no campaigns: do not treat the campaigns as absent; record the discrepancy and use the confirmed browser context, or stop.
- The goal exists, but there are no fresh completions: do not promise learnability, and do not substitute a microconversion without a user decision.
- The campaign copy still contains the source URL or texts: do not include it in the package until that is fixed.
- validate_template is unavailable or does not apply: do not mark the template as valid; run page_get and a public check, and record the limitation.
- It is unclear whether the budget is shared or individual: stop strategy setup and clarify.

## Prohibitions

- Do not publish one URL for different intents without an agreed single mode.
- Do not reveal the internal A/B variant name in the public landing copy.
- Do not mix icons from different sets, and do not use emoji as UI icons.
- Do not write landing CSS without reading references/VISUAL.md. Do not load GSAP, Tailwind, or CDN UI frameworks.
- Do not intercept `$MFORM_N$` submission via `fetch` or `FormData`.
- Do not crop screenshots to force equal card height.
- Do not leave a CTA without UTM on signup, pricing, or templates.
- Do not treat registration or publication as a quality conversion by default.
- Do not silently correct factual event identifiers that contain typos.
- Do not delete test campaigns without an exact ID list and owner confirmation.
- Do not launch campaigns, send them to moderation, or top up the account without an explicit command.

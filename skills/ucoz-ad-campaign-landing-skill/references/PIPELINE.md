# Pipeline v2

## 0. Read-only audit

Read AGENTS.md, the source materials, and the current state of uCoz, Yandex Metrica, and Yandex Direct. Record the evidence before any changes.

## 1. Brief

Fill in the fields from INTAKE.md. Choose a branch:

    ready-made ads → normalization and message match
    no ads → demand research and generation

    publish_to_ucoz=false → local final files
    publish_to_ucoz=true → LANDING_PLAYBOOK.md

    direct_surface=prepare_only → the ad package only
    direct_surface=api → DIRECT_API.md
    direct_surface=browser → DIRECT_BROWSER_AND_PACKAGES.md

## 2. Experiment matrix

Build every test cell before writing HTML. Each cell has unique values for:

- segment_id;
- internal_label;
- hypothesis;
- slug;
- utm_content;
- destination_url;
- the expected campaign name.

Check that the public copy does not reveal internal_label.

## 3. Copy and keywords

Follow COPY.md and, when needed, RESEARCH.md. Align price, CTA, geo, and bans. For each segment, prepare two ad variants and negative keywords.

## 4. Landing pages

1. Create a shared design system for the series.
2. Generate the variant from the matrix.
3. Check links and UTM parameters.
4. Publish through LANDING_PLAYBOOK.md if that is allowed.
5. Run page_get and validate_template, or record why they do not apply.
6. Check the public URL and the sizes 320/375/768/1024+.

Do not issue final_url before a successful publish.

## 5. Yandex Metrica

Install the confirmed counter on every page. Follow MEASUREMENT.md and save the three status layers of each goal.

## 6. Campaigns

Create one campaign per test cell if that granularity is approved. Campaigns remain drafts.

After creating or duplicating, reconcile the campaign, the ad group, and the ad. Check the final URL at the level of a real click, not only the campaign name.

## 7. Package strategy

After every campaign in one niche has been checked:

1. get an explicit decision on the shared weekly budget;
2. create or update the package;
3. add only the approved campaign_id values;
4. choose a verified goal;
5. save them as drafts;
6. read the package card after saving;
7. find extra copies, but do not delete them without confirmation.

## 8. Final audit

Check:

- URL, UTM, and message match;
- price and CTA;
- screenshots and responsiveness;
- campaign_id and package_strategy_id;
- the shared budget, the counter, and the goal;
- absence of launch and of moderation;
- absence of unaccounted duplicates.

## 9. Handoff

Produce:

- segments.json;
- a “landing page → campaign → package” table;
- before/after evidence;
- a list of constraints and requirements before launch;
- a short Telegram report with clickable URLs and the shared budget of the packages.

The full format is OUTPUT.md.

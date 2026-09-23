# Yandex Direct via the browser: drafts and package strategies

Read this file when the API is unavailable for the required organization context, the user asks to work in an open account, or a package strategy needs to be checked visually.

## Choosing the surface

1. First check whether a suitable API or connector exists for the operation.
2. If the user explicitly chose the browser, or the API does not see the required organization context, use an authorized browser.
3. Before the first browser operation, load the skill for controlling the chosen browser and follow its confirmation rules.
4. An open tab is context only. Make sure the correct login and organization mode are selected.

An API token may be valid for a personal client but not return campaigns that are visible in organization mode. This does not prove the campaigns are absent. Record:

- which login the API confirmed;
- which mode is indicated by the ulogin parameter in the account;
- which Client-Login values were checked;
- whether the same campaign_id values are visible via the API and the interface.

If the match is not confirmed, do not perform mutations via the API.

## Read-only snapshot before changes

Save to an evidence file:

- date and timezone;
- account URL and organization mode, without tokens;
- list of campaign_id, names, and statuses;
- package strategies: ID, name, budget, goals, and members;
- Yandex Metrica counter and optimization goal;
- what the user allowed to change;
- what must remain unchanged.

Do not rely on the page title or the campaign name. The primary identifier is the numeric ID.

## Creating campaigns

By default, create drafts only.

Recommended name format:

    {brand} / {niche} / {price|no price} / {signup|templates|catalog}

For each campaign, record:

| Level | What to check |
|---|---|
| Campaign | ID, name, promotion object, placements, dates, strategy, counter, goals |
| Ad group | ID, name, geo, keywords, negative keywords |
| Ad | ID, H1, H2, text, CTA, final URL, UTM |

If a campaign was created by duplication, checking all three levels is mandatory. Renaming a campaign does not automatically change the promotion object, the ad texts, or the ad links.

### URL check after copying

Compare the expected URL with three sources:

1. the campaign “Promotion object” card;
2. the link inside the ad group or the ad;
3. the public final URL with UTM.

If they differ, do not include the campaign in the package. First establish which URL is actually used on click.

## Package strategy

A package combines several campaigns of one business hypothesis and allocates one shared weekly budget.

Before creating, clarify:

- which campaign_id values are in the package;
- the weekly budget of the whole package;
- the strategy and payment;
- the counter and the goal;
- whether the campaigns should remain drafts.

Recommended name:

    {brand} / {niche} / {N} landings / {weekly budget}

After saving, check the strategy card and the campaign list. The budget shown on each campaign may be the package budget. In the report, write “shared package budget”, and do not multiply it by the number of campaigns.

## Goals and strategy

The goal is chosen according to references/MEASUREMENT.md. In the strategy card, confirm separately:

- counter;
- goal ID and name;
- number of recent conversions and the window;
- payment model;
- shared weekly budget;
- absence of unplanned extra goals.

Do not assume a goal is selected by the strategy merely because it exists in the Conversion Center.

## Navigation in the SPA account

The Direct interface may not finish a direct navigation, or it may leave old text in the sidebar. Safe order:

1. after a click, check the current URL and a fresh DOM snapshot;
2. if navigation did not happen, do not repeat the action blindly;
3. check for a dialog, an unsaved form, or a blocking layer;
4. if needed, open a new tab of the same authorized browser on the exact list page;
5. after each change, read the fresh status, toast, or object card.

Old text in the sidebar is not proof of the current object. Match campaign_id from the URL and the main content.

## Search and bulk actions

Before a bulk action:

1. filter the list by a unique attribute;
2. read all matches and statuses;
3. record the exact selected set of campaign_id values;
4. make sure live campaigns are not in the set;
5. only then open the actions menu.

To delete drafts, the user must confirm the exact IDs immediately before pressing the final “Yes”. After deletion, check the system notification and repeat the same search. Do not call deletion archiving.

## Duplicates and leftover test campaigns

After package assembly, search for:

- “копия” (copy);
- test prefixes;
- the range of new IDs;
- campaigns outside the approved packages.

Do not delete what you find automatically. First show the user the exact IDs, names, status, and package membership.

## Final check before launch

For each campaign:

- status “Draft” or another expected status;
- the correct landing and UTM;
- the correct text and keyword segment;
- no CTA that is absent from the landing;
- only the agreed placements;
- automatic recommendations match the owner’s decision;
- the counter and goal match the package strategy.

For each package:

- the correct members;
- the shared weekly budget;
- no accidental copies;
- the goal has an acceptable recent volume;
- launch and moderation were not performed without a command.

## Evidence template

    Date/timezone:
    Organization mode:
    Package strategy: ID, name
    Shared weekly budget:
    Campaigns: [ID...]
    Counter:
    Goal:
    Campaign statuses:
    Read-only state before:
    Change:
    Check after:
    Not performed: launch / moderation / top-up / deletion

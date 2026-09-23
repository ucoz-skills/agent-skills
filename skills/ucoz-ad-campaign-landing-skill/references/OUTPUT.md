# Result Format

## 1. Manifest

For each test cell, save:

| Field | Contents |
|---|---|
| segment_id | stable internal ID |
| niche | niche |
| hypothesis | what is being tested |
| price_visibility | shown / hidden |
| destination | signup / thematic_templates / all_templates / other |
| internal_label | service name, not public copy |
| landing_url | published URL without UTM |
| destination_url | destination CTA |
| final_url | ad URL with UTM |
| page_id | uCoz page ID |
| validation_status | validate_template / fallback / failed |
| campaign_id | Yandex Direct campaign ID |
| ad_group_id | ad group ID |
| ad_id | ad ID |
| package_strategy_id | package strategy ID |
| package_weekly_budget | shared weekly budget of the package |

## 2. Linkage table

    | Micro-segment | Landing page | Campaign | Status | Package |

URLs must be clickable. State the budget at package level when it is shared.

## 3. Goals

    | Event | product_event_status | metrika_goal_status | direct_conversion_status | Decision |

Add the date, the timezone, the analysis window, counter_id, and goal_id. Do not collapse the table into one overall status.

## 4. Package strategies

For each package:

- ID and the exact name;
- niche;
- campaign_id values of the members;
- shared weekly budget;
- strategy and payment model;
- counter and goal;
- campaign status;
- what was not done: launch, moderation, top-up.

## 5. Evidence

Store separately:

- the read-only state before;
- the user’s explicit command to change something;
- the actual change;
- the check after;
- errors and fallback;
- duplicates found, and the decision about them.

Do not include tokens, cookies, passwords, or personal data.

## 6. Short report for the owner

Structure:

1. how many landing pages, campaigns, and packages were created;
2. which micro-segments are being tested;
3. links to every landing page;
4. the budget of each package and the total budget;
5. the optimization goal and its limitation;
6. the current status: drafts / launched;
7. which decision is needed from the owner.

## 7. Telegram version

The text must be copyable without tables:

    Prepared: N landing pages and N search campaigns.
    Micro-segmentation: {factors and values}.

    {Niche 1}:
    • {variant}: https://...
    ...
    Package No. ..., shared budget ... ₽/week.

    {Niche 2}:
    ...

    Total: ... ₽/week. Campaigns are drafts.
    Optimization: {goal}, counter {counter_id}; limitation: {if any}.
    Needs approval: {budget / goal / launch}.

Do not write “10 000 ₽ for each campaign” when that amount is the single shared budget of the package strategy.

## 8. Handing off local files

In the final answer, give clickable absolute links to:

- SKILL.md or the manifest;
- the zip package, if one was created;
- the evidence or the change report;
- local HTML/JSON only if the user needs them.

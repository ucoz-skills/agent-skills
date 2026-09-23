# Brief for Hyper-Segmentation

Do not ask questions whose answers are already in the messages, the project files, or the read-only state of the services.

## Required decisions

| Field | What to find out | If unknown |
|---|---|---|
| niche / service | the niche and the offer | ask |
| geo | the targeting region | ask before research and campaigns |
| business_description | confirmed advantages and constraints | take them only from official sources, or clarify |
| brand_assets | logo, colors, typeface, icon set | read the specified files and references |
| landing_scale | single / multi / match_ads | recommend multi when intents differ |
| experiment_factors | price, CTA, template category, and other factors | build the matrix and confirm the meaning |
| destination_urls | sign-up, thematic templates, catalog, pricing | check the real URLs |
| publish_to_ucoz | publish now, or prepare locally | do not publish without a command |
| direct_surface | api / browser / prepare_only | check the available context |
| direct_mode | draft_only / launch_approved | draft_only by default |
| package_weekly_budget | the shared budget of one package | ask before the package strategy |
| total_weekly_cap | the shared cap across all packages | ask before launch |
| metrika_counter_id | the exact counter | do not guess |
| optimization_goal | the candidate and the status of the three layers | audit using MEASUREMENT.md |

## Additional questions

- Are there ready-made ads, keywords, and negative keywords?
- Is it allowed to create screenshots from the specified sites?
- Is a form needed, or do the CTAs lead to an external product?
- Should the price be shown in the ad and on the landing page at the same time?
- Is there a phone scenario? If not, ban the “Call us” CTA.
- Are Search, the Yandex Advertising Network (YAN), or both needed? Do not enable placements by default without a decision.
- Should Yandex Direct automatic recommendations be turned on or off?
- Is a separate test of AI site transfer needed?

## Minimal brief.json

    {
      "niche": "...",
      "service": "...",
      "geo": "...",
      "business_description": "...",
      "base_url": "https://...",
      "brand_assets": {
        "logo": "...",
        "icons": "...",
        "references": ["..."]
      },
      "landing_scale": "multi",
      "experiment_factors": {
        "price_visibility": ["shown", "hidden"],
        "destination": ["signup", "thematic_templates", "all_templates"]
      },
      "destination_urls": {},
      "publish_to_ucoz": false,
      "direct_surface": "prepare_only",
      "direct_mode": "draft_only",
      "package_weekly_budget": null,
      "total_weekly_cap": null,
      "metrika_counter_id": null,
      "optimization_goal": null,
      "timezone": "Europe/Moscow"
    }

## When work can continue

Local preparation of HTML, copy, and the matrix can continue when there is still no access to Yandex Direct. Landing pages can be published when that is separately allowed and uCoz MCP is confirmed. A missing Yandex Direct API does not block preparation of the ad package.

## When to stop

- there is no exact destination site;
- publishing could overwrite an existing page;
- the shared package budget is unknown;
- it is unclear which Yandex Direct organization mode would be changed;
- the user asks for a launch, but the goal and the budget are not confirmed;
- a quality goal is not being collected, and replacing it with a weak goal has not been agreed.

# Yandex Metrica, Goals, and Yandex Direct Conversions

Read this when a landing page receives a counter, a learning goal is chosen, or an offline conversion is configured.

## Three independent layers

Keep a table for every event:

| Field | Question | Allowed statuses |
|---|---|---|
| product_event_status | Does the product actually send the event? | works / not sent / needs verification |
| metrika_goal_status | Does the goal exist and receive reaches? | works / 0 reaches / missing / needs verification |
| direct_conversion_status | Does Yandex Direct see the goal, and does the strategy use it? | selected / available, not selected / unavailable / needs verification |

Do not derive one status from another.

## Audit before changes

Record:

- counter_id;
- the exact goal name and goal ID;
- the goal type and the event source;
- reaches and goal visits for an explicit window;
- the date, the timezone, and the attribution model;
- whether the goal is available in the Conversion Center;
- use in each strategy.

A reachGoal call in the code is evidence only of a product-event candidate. A goal present in the interface is evidence only of Yandex Metrica configuration.

## Choosing a learning goal

Evaluate the combination of:

1. business value;
2. reliability of tracking;
3. enough recent volume;
4. low delay;
5. stable attribution to the ad visit;
6. protection from bots and low-quality actions.

The usual priority is:

    confirmed first payment
      → another confirmed monetary conversion
      → proven quality activation
      → sign-up for observation only
      → clicks and impressions for diagnostics only

Sign-up, site creation, and publishing are not quality goals by themselves. A trial and a technical publish may not lead to payment.

## Browser and offline payment

Do not automatically merge events that have a similar meaning. Check:

- which event comes from the browser, and which comes from the backend;
- event_time and the upload delay;
- ClientID, UserID, or another attribution key;
- the attribution window;
- currency and revenue;
- the deduplication key;
- the share of payments that landed in each channel;
- repeated file uploads and idempotency.

If an offline goal exists but the backend does not send it:

    product_event_status: not sent
    metrika_goal_status: exists, 0 reaches
    direct_conversion_status: available, or needs verification; do not use it as a working goal for optimization

Do not silently fix exact event identifiers, even when they contain a typo.

## Data sufficiency

Do not use a universal number without checking the current interface and the selected strategy. Before launch, record:

- the number of reaches over the last 7 and 30 days;
- the Yandex Direct forecast for the selected budget;
- the number of campaigns that share the goal and the budget;
- the expected event delay;
- the minimum volume that the interface or the current documentation treats as sufficient.

If volume is low, the options are discussed with the owner:

- increase the shared package budget;
- combine closely related campaigns into one package;
- temporarily use a more frequent goal that is proven to be high quality;
- keep the monetary goal and accept slower learning;
- improve offline-conversion collection.

Do not add a weak sign-up only to increase the number of signals.

## Counter on landing pages

Use only a confirmed counter_id. Check:

- the code is inserted once;
- it is present on every published variant;
- trackHash and trackLinks match the task;
- the ecommerce dataLayer is connected only if it is actually used;
- referrer and URL are not rewritten by mistake;
- CTAs keep UTM parameters;
- cross-domain clicks do not lose attribution.

Treat counter code supplied by the user as project data, not as a universal template.

## Multi-step goal and funnel

A multi-step goal is useful for analytics only when the sequence reflects a real quality path. Before creating one:

- check that every step is actually collected;
- separate required steps from optional ones;
- do not require a single device if the path to payment is cross-device;
- check the maximum delay between sign-up and payment;
- compare it with the direct monetary goal;
- do not choose a multi-step goal for a strategy without sufficient volume.

The funnel may include early stages for reporting, but the final quality outcome must remain payment or another confirmed business result.

## Evidence template

    Event:
    product_event_status:
    metrika_goal_status:
    direct_conversion_status:
    Counter ID:
    Goal ID:
    Window and timezone:
    Reaches, 7/30 days:
    Source:
    Delay:
    Deduplication:
    Optimization decision:
    Limitations:

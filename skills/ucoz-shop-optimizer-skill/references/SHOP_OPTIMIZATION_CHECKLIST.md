# Shop Optimization Checklist for uCoz Stores

## Critical

- Product page template is broken.
- Add-to-cart button is missing or broken.
- Product price is not displayed.
- Checkout/service form template is broken.
- Important public categories are hidden.
- Important products are accidentally hidden.
- Accidental `noindex` on product/category pages.
- Currency rate issue affecting displayed prices.
- Order status bulk change risk.

## High

- Empty category description.
- Missing category meta title or meta description.
- Duplicate category names or unclear hierarchy.
- Weak product names.
- Empty or very weak product short descriptions.
- Empty full product descriptions.
- No breadcrumbs on category/product pages.
- No clear delivery/payment/return hint near the purchase decision.
- Product template does not expose availability/stock clearly.
- Product cards do not show old price/discount consistently when used.
- Key products have no usable image.
- Existing product photos are inconsistent in style or quality.

## Medium

- Weak category slugs.
- Important categories missing from menu.
- Weak product snippets in lists.
- No Product/Offer structured data where variables allow it.
- No Open Graph fallback for product pages.
- No internal links between related categories.
- Poor mobile layout for product cards or checkout blocks.
- Duplicated boilerplate text in many product descriptions.
- No category banner where one would improve navigation or conversion.
- No alt-text strategy for product/category visuals.

## Low

- Cosmetic CTA wording improvements.
- Better formatting for product specs.
- Optional FAQ block on category/product pages.
- Optional trust badges.
- Optional image alt improvements if editable in the template.
- Optional lifestyle/supporting visuals for category pages.

## Protected fields by default

Do not change these fields unless the user explicitly requests it:

- `price`
- `price_old`
- `price_in`
- `stock`
- `art`
- `type`
- `hide`
- `cats_add`
- variations/options
- category visibility
- category access groups
- currency rates
- order status
- order courier
- order group/archive/trash state
- order items
- checkout submission
- cart deletion/clearing
- existing product photos
- existing category images/banners

# uCoz Shop Optimizer — Example Prompts

## Audit

```text
Audit my uCoz shop without making changes: categories, product template, cart, checkout and menu. Return a prioritized issue list.
```

```text
Audit shop categories: names, URLs, descriptions, meta title, meta description, hierarchy and visibility. Do not change anything.
```

## Category optimization

```text
Find categories without descriptions and suggest copy. Do not change URLs, access groups or visibility.
```

```text
Optimize the first 10 categories: meta title, meta description and short description. Show a before/after table first.
```

## Product optimization

```text
For this product list, prepare name, brief and html_dscr. Do not touch prices, stock, SKU/article or product types.
```

```text
Improve descriptions for 20 products, but do not change commercial fields. Prepare a batch with preview.
```

## Scope selection

```text
Before starting, ask what to work with: one product, selected products, one category or products inside a category.
```

```text
Do not optimize the whole shop at once. First suggest choosing a category or a product list.
```

## Images

```text
For these 20 products, prepare image generation prompts in a unified style: white background, realistic e-commerce, no text on the image.
```

```text
Generate images for products without photos and add them through the Online Shop API: /shop/editgoods/img_add, file_add_cnt and file_add_*. If appearance is unknown, create only a careful concept illustration and note that in the report.
```

```text
For each product card, prepare: prompt, alt, filename, then add the product image via /shop/editgoods/img_add. Do not replace existing images.
```

```text
For the selected category, generate a banner and update the category image via /shop/edit_cat with mode=img. Show the plan first.
```

```text
Create a banner for the "Accessories" category: wide format, clean modern style, no text on the banner. Show the concept first.
```

## Template optimization

```text
Check the product page template: H1, price, old price, availability, cart button, breadcrumbs and Product Schema. Show the plan first.
```

```text
Add Product/Offer JSON-LD to the product template. Use only available uCoz variables and validate the template before saving.
```

## Orders and operations

```text
Generate an order report for the past month: new, paid, processing, delivered and cancelled. Do not change statuses.
```

```text
Check stuck orders in New and Paying status older than 7 days. Do not change anything, list only.
```

## Creation

```text
Create a new "Accessories" category, but first show the fields: name, URL, description, meta title and meta description.
```

```text
Create 5 products from this list in hidden state so I can review the cards before publishing.
```

## MCP/API connection

```text
Connect to ucoz-mcp, take the current site and API key from MCP and optimize the selected products through the shop API.
```

```text
For products 101, 102 and 103, generate images and add them via /shop/editgoods/img_add. Do not replace existing images.
```

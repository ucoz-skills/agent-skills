# Scope Rules for uCoz Shop Optimizer

Before choosing a scope, call `list_sites`, then `select_site(site_id)`. Shop tools use that selected site. If the user did not switch sites, do not pick another site and do not ask for a uAPI key. The current MCP does not provide one.

## Mandatory scope gate

Before any product/category optimization, the agent must know the exact work scope.

Allowed scopes:

- one product;
- selected products;
- one category;
- selected categories;
- products inside one chosen category;
- full-shop read-only audit;
- full-shop write optimization only after explicit approval and a batch plan.

## Default behavior

- Do not optimize all products by default.
- Do not optimize all categories by default.
- If the user says “optimize the shop” without details, start with a short scope question.
- For read-only audits, broad scope is acceptable.
- For write operations, selected objects are required.

## Scope question

Use a short question:

“What should I work with: one product, selected products, one category, products in a specific category, or only a read-only audit of the whole shop?”

## Batch limits

- Up to 20 products/categories per write batch by default.
- More than 20 requires a batch plan.
- Each batch must have a preview table and protected-field list.

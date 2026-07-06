# Scope Rules for uCoz Shop Optimizer

Before selecting scope, the agent connects to `ucoz-mcp` and uses the active site from the MCP context. If the user has not explicitly switched sites, do not choose another site or ask for a separate API key.

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

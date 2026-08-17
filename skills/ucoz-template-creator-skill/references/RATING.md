# Native rating and custom sprite

## Generator contract

Use the verified `<?$RSTARS$('24','','1','float')?>` call on vote-capable full material pages. It is the native uCoz generator and owns the five hit targets, hover state, current state, tooltip, and voting behavior.

Keep `$RATING$` only as an availability condition or numeric value such as `0.0`. It may appear as list metadata, but it must never replace the interactive generator. `$RATE_FORM$` can exist in a module template; do not substitute it for the tested custom-star call without live verification. Never replace the generator with five static SVG stars or Unicode characters.

## Sprite contract

The generator reads these framework `config` keys:

- `rstars_b_image`, `rstars_b_size` — large rating;
- `rstars_s_image`, `rstars_s_size` — small rating.

Each image is one vertical sprite with three equal square rows:

1. normal;
2. hover;
3. selected/current score.

For size 24, use 24×72; for size 16, use 16×48. Transparent PNG is the safest platform-compatible format. SVG is acceptable only when it preserves the same vertical geometry and is verified on the live widget.

Use [assets/rating-sprite-template.svg](../assets/rating-sprite-template.svg) as the editable 24×72 reference. Recolor all three rows for the selected design, URL-encode the complete SVG when embedding it as `data:image/svg+xml`, and set the corresponding size to `24`. The asset is a state image consumed by uCoz, not five inline stars.

Configure the sprite in the framework `<config>` block. Metadata icons are a separate SVG system and must not be used as rating states.

## Full-material render pattern

```html
<?if($RATING$)?>
  <div class="entry-page__rating"><?$RSTARS$('24','','1','float')?></div>
<?endif?>
```

Verify the exact availability condition against the target module. Preserve the native generated markup and scope cosmetic CSS after inspecting the live DOM.

## QA

- Every vote-capable full material page contains the generator, not only numeric `$RATING$`.
- Exactly one row of five stars is visible at rest.
- Hover does not expose the second sprite row below the stars.
- Current score clips horizontally without exposing adjacent rows.
- All five hit targets, tooltip, and voting interaction work.
- Numeric score text may be hidden only with a narrowly scoped selector after inspecting live markup; never hide the voting control.
- Validate empty, partial, full, hover, and already-voted states on desktop and mobile.

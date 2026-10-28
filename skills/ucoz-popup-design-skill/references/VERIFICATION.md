# Verification

Test the real site windows as well as the demos. A synthetic demo does not prove every site form works.

## Matrix

| Area | Desktop | 375px |
|---|---|---|
| Ordinary window | ☐ | ☐ |
| Confirmation | ☐ | ☐ |
| Form in a window | ☐ | ☐ |
| Long content | ☐ | ☐ |
| Toasts: all four types | ☐ | ☐ |
| Site light mode | ☐ | ☐ |
| Site dark mode (if the site has one) | ☐ | ☐ |

## Acceptance criteria

- Buttons and close icons stay visible; no accidental horizontal scroll.
- A short confirmation shows its buttons without scrolling.
- Text, select, checkbox and radio fields; checked, disabled, readonly and error states look right.
- Each select shows exactly one arrow; focus outlines are not clipped; keyboard activation works.
- Key text pairs reach 4.5:1 contrast, including custom palettes.
- Close, Escape, drag, resize, maximize/restore and harmless callbacks work.
- Menus, lightbox, Control Panel dialogs and content outside the popups are unchanged.
- A long toast is as tall as its visible content. After a viewport change, reopen the window if the native height is stale.

## Never

- Submit a real form for a visual check.
- Claim complete accessibility: CSS cannot add missing ARIA roles, focus traps or tabindex.

## Report

List what was tested, on which pages and widths, and every variant that was not tested.

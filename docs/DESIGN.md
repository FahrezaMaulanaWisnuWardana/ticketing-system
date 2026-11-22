# UI craft rules (applied)

This project follows the design-engineering guidance from [`emilkowalski/skills`](https://github.com/emilkowalski/skills) and the [Vercel Web Interface Guidelines](https://github.com/vercel-labs/web-interface-guidelines). These are the rules already encoded in this codebase — keep them when adding UI.

## Motion

- No animation for high-frequency/keyboard actions. `x-shortcut` sets `html[data-keyboard]` for one tick, which suppresses modal/toast transitions.
- `--ease-out: cubic-bezier(0.23, 1, 0.32, 1)`; never `ease-in`, never `transition: all`.
- UI transitions are 150–180 ms; only `transform` and `opacity` (plus colors for buttons) are transitioned.
- Press feedback: `.btn:active { transform: scale(0.97) }`.
- `.modal-panel` enters from `scale(0.96)` + opacity (never `scale(0)`), centered; popovers would use a trigger-origin transform, but this app uses centered modals only.
- `@media (prefers-reduced-motion: reduce)` removes movement but keeps short opacity feedback.
- Hover effects are gated behind `@media (hover: hover) and (pointer: fine)`.

## Typography & numbers

- Headings: `letter-spacing: -0.02em`, `text-wrap: balance`.
- Body/prose: `max-width: 65ch`.
- Numeric columns and counters use `.tabular` (`font-variant-numeric: tabular-nums`).
- Identifiers (`IT-123`, project keys) use `translate="no"`.

## Content & time

- `App\Support\DateFormat`: relative time for ≤7 days, otherwise absolute localized date/time via `IntlDateFormatter`. No `diffForHumans()` on old timestamps.
- Counts use `trans_choice('common.tickets_count', $n)` so English says "1 ticket", not "1 tickets".
- Long table cells wrap (`min-width: 0`, `break-words`).

## Accessibility & interaction

- Skip link to `#main-content`, visible focus rings everywhere (never `outline-none` without a replacement).
- Async notices are announced through an `aria-live="polite"` live region (`notify` event).
- Destructive actions require confirmation; keyboard alternatives exist for all drag interactions.
- `touch-action: manipulation`, deliberate `-webkit-tap-highlight-color`, and no `autofocus` on mobile-facing inputs.

## Review format

When reviewing UI changes, use a single table with `Before | After | Why` columns (one row per issue), as required by the `emil-design-eng` skill.

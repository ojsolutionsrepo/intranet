# Design System (OJ-INTRA-DS-001)

Source of truth: `05_Design_System.html`. Tokens are the contract; components are examples. When they disagree, tokens win. Modules never redefine tokens — import once in the app layout.

## Principles

1. Clarity over decoration
2. Dense but breathable
3. Consistent, not clever
4. Accessible by default (WCAG 2.1 AA floor)

## CSS variables

```css
:root {
  /* Brand — OJ slate */
  --oj-900: #0e1a2b; --oj-800: #16273d; --oj-700: #1f374f; --oj-600: #2c4a67;
  --oj-500: #3d6489; --oj-400: #5b83a8; --oj-300: #8aa8c4; --oj-200: #bcd0e0;
  --oj-100: #e2ebf2; --oj-50: #f2f6fa;

  /* Signal — amber (actions & focus ONLY) */
  --sig-600: #b5641a; --sig-500: #d97b22; --sig-400: #e89a4d; --sig-100: #fbeeda;

  /* Semantic — state only */
  --ok-600: #2f6d42; --ok-100: #e2f0e6;
  --warn-600: #9a6212; --warn-100: #faeeda;
  --err-600: #a3312d; --err-100: #fae5e4;
  --info-600: #245d8a; --info-100: #e2eef7;

  /* Ink & paper */
  --ink-900: #151a1f; --ink-700: #3a4249; --ink-500: #6b747c; --ink-300: #9ba3aa;
  --paper-0: #ffffff; --paper-1: #f7f8f9; --paper-2: #eef0f2;
  --line: #dde1e5; --line-strong: #c3c9cf;

  /* Type */
  --font-display: 'Archivo', sans-serif;
  --font-body: 'Archivo', sans-serif;
  --font-voice: 'Newsreader', Georgia, serif;
  --font-mono: 'JetBrains Mono', monospace;

  /* Space (4px base — never invent in-betweens) */
  --s1: 4px; --s2: 8px; --s3: 12px; --s4: 16px;
  --s5: 24px; --s6: 32px; --s7: 48px; --s8: 64px;

  /* Radius */
  --r-sm: 4px; --r-md: 6px; --r-lg: 10px; --r-full: 999px;

  /* Liquid glass (chrome: header, sidebar, menus — not dense content cards) */
  --glass-bg: rgba(255, 255, 255, 0.72);
  --glass-blur: 16px;
  --glass-sidebar-bg: rgba(14, 26, 43, 0.84);
}
```

Support `data-theme="light|dark|system"` on `<html>` (user toggle). Default is **dark** (Governex-like deep canvas + glass panels, using OJ slate / amber signal — not cyan). Dark tokens remapped for ink/paper/line/atmosphere. System follows `prefers-color-scheme`. Persist choice in `localStorage` key `oj-theme`.

## Tailwind theme.extend

```js
colors: {
  oj: {
    900: '#0e1a2b', 800: '#16273d', 700: '#1f374f', 600: '#2c4a67',
    500: '#3d6489', 400: '#5b83a8', 300: '#8aa8c4', 200: '#bcd0e0',
    100: '#e2ebf2', 50: '#f2f6fa',
  },
  signal: { 600: '#b5641a', 500: '#d97b22', 400: '#e89a4d', 100: '#fbeeda' },
  ok: { 600: '#2f6d42', 100: '#e2f0e6' },
  warn: { 600: '#9a6212', 100: '#faeeda' },
  err: { 600: '#a3312d', 100: '#fae5e4' },
  info: { 600: '#245d8a', 100: '#e2eef7' },
},
fontFamily: {
  display: ['Archivo', 'sans-serif'],
  voice: ['Newsreader', 'serif'],
  mono: ['JetBrains Mono', 'monospace'],
},
borderRadius: { sm: '4px', md: '6px', lg: '10px' },
```

## Typography scale

| Role | Font | Size / tracking |
|------|------|-----------------|
| Page title (h1) | Archivo 700 | 40px / -0.03em |
| Section (h2) | Archivo 600 | 24px / -0.02em |
| Card / widget (h3) | Archivo 600 | 16px |
| Body | Archivo 400 | 15px / 1.55 |
| Editorial voice | Newsreader 400 | 19px / 1.5 |
| Meta / refs | JetBrains Mono | 12px |

Newsreader only for editorial moments (welcome, policy intro). Mono for DOC refs, versions, timestamps, RAG labels.

## Shell layout

- Sidebar: sticky, `oj-900` background, 240px, brand mark with `sig-500` square + "OJ"
- Main: `paper-1` background, max content ~1100px, dense padding (`s5`–`s7`)
- Nav labels: uppercase micro (10px, letter-spacing 0.12em, `oj-400`)

## Components

### Buttons

One primary per view (amber). Secondary = slate. Ghost = tertiary. Danger = destructive + confirm.

| Class | Use |
|-------|-----|
| `btn btn-primary` | Primary action (`sig-500`) |
| `btn btn-secondary` | Secondary (`oj-700`) |
| `btn btn-ghost` | Cancel / tertiary |
| `btn btn-danger` | Destructive |
| `btn-sm` | Compact |

Focus: `outline: 2px solid var(--sig-400); outline-offset: 2px` on `:focus-visible`.

### Forms

Labels always visible (never placeholder-only). Focus border `sig-500` + soft ring `sig-100`.

### Badges (status)

Colour never alone — always with label. Classes: `badge badge-ok|warn|err|info`.

Policy chips: Current / Due for review / Overdue map to ok / warn / err.

### RAG (projects)

```html
<span class="rag rag-g">● GREEN · on track</span>
<span class="rag rag-a">● AMBER · at risk</span>
<span class="rag rag-r">● RED · blocked</span>
```

### Alerts

Left-border accent + semantic fill: `alert alert-info|warn|err`. Site-wide announcement banners of type `alert` use the same pattern and are dismissible per user.

### Tables

Uppercase micro headers, row hover `paper-1`, mono for versions/dates/refs. Dense by design.

### Blade components

```blade
{{-- resources/views/components/badge.blade.php --}}
@props(['status' => 'info'])
<span class="badge badge-{{ $status }}">{{ $slot }}</span>

<x-badge status="ok">Current</x-badge>
```

Prefer shared Blade components under `resources/views/components/` over per-module duplicates.

## Accessibility rules

- Body text contrast AA (7:1+). No body text on colours lighter than the -600 stop.
- Amber `sig-500` on white: large text / UI only — not body copy.
- Every interactive element: amber `:focus-visible` ring — never removed.
- Status always has label or icon as well as colour.
- Targets ≥ 40px. Respect `prefers-reduced-motion`.
- Verify at 360 / 768 / 1024 / 1440px.

## Do / Don't

| Do | Don't |
|----|-------|
| Use `signal` only for primary actions and focus | Use purple gradients or cream/terracotta defaults |
| Use semantic colours for state only | Use amber or semantic colours decoratively |
| Stick to 4px spacing scale | Invent 20px gaps |
| One primary button per view | Multiple competing amber CTAs |
| Dense tables and lists | Card-heavy marketing layouts |

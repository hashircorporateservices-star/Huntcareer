# HuntCareer design system

Deliberately distinct from JobCopilot and the other job-apply tools, which all cluster
on **violet/indigo SaaS gradients**. HuntCareer's identity is a calm operator's console.

## Palette
| Token | Hex | Use |
|---|---|---|
| Pine (brand-600) | `#1f6344` | primary buttons, links, active states, positive |
| Pine deep (brand-700/900) | `#1a4f38` / `#123324` | headers, hovers |
| Pine tint (brand-50/100) | `#eef6f1` / `#d5e9dd` | badges, soft fills |
| Paper | `#faf9f6` | app background (warm, not cool gray) |
| Ink (stone-900) | `#1c1917` | text |
| Stone (200–500) | Tailwind `stone` | borders, secondary text |

One brand hue only — pine does double duty for brand *and* success, so the UI stays
disciplined instead of the multi-color badge soup competitors use.

## Type — three roles
- **Display:** Space Grotesk — headings, used with restraint.
- **Body:** Inter — everything readable.
- **Figures:** IBM Plex Mono via the `.figure` class — **the signature.** Every number
  (match %, "1,111 matches", salaries, credits, counts) renders in mono with tabular
  figures. This product is full of data; setting it in mono makes it read like an
  instrument panel, not a consumer app. It's the one thing that should feel unmistakably
  HuntCareer.

## Rules
- Spend boldness in one place: the mono figures + pine. Everything else stays quiet.
- Larger radii (`rounded-xl`/`2xl`) and generous spacing → premium, calm.
- Accessible floor: keyboard focus visible, works to mobile, motion kept minimal.

## How it's wired
- `tailwind.config.ts` — `brand` (pine) color scale + `display`/`sans`/`mono` families.
- `src/app/globals.css` — font imports, `--paper`/`--ink` tokens, and the `.figure` class.
- Components use `brand-*` and `stone-*` utilities only (no `indigo`/`slate`).

# WP.org Assets Creative Brief & Grok4 Prompts

This document contains the creative brief and ready-to-copy prompts for generating the plugin icon and banner.

## Brand & Context
- Product: WP‑Dapp — WordPress ↔ Hive blockchain publishing (via Hive Keychain)
- Values: security, simplicity, decentralization, creators-first
- Visual tone: clean, modern, friendly developer tool; avoid crypto hype
- Colors: draw from WordPress blues and Hive red; keep contrast accessible

## Deliverables & Specs
- Icon: 256×256 PNG (also export 128×128)
- Banner (high‑res): 1544×500 PNG
- Banner (low‑res): 772×250 PNG
- Optional vector master: SVG for both icon and banner

## Visual Directions
- Motif: combine WordPress “W” circle motif and Hive hex/rib motif in a tasteful, abstract way
- Emphasize “bridge” concept: subtle link/bridge element between WP and Hive shapes
- Style: flat or semi‑flat, minimal gradients, crisp edges; strong silhouette
- Backgrounds: solid or soft gradient; ensure legibility for tiny sizes

---

## Prompt 1 — Plugin Icon (256×256)
Copy the full block to Grok4:

"""
You are generating a plugin icon PNG at 256×256 for a WordPress plugin named “WP‑Dapp.” The plugin bridges WordPress publishing with the Hive blockchain using Hive Keychain.

Design goals:
- Minimal, flat/semi‑flat style; crisp edges and high contrast
- Abstract combination of a WordPress-style “W” circle and a Hive hex motif
- Subtle “bridge” connector between the two forms (unity, not conflict)
- Primary palette: WordPress blues (#006799 to #21759B) and Hive red (#E31337), with neutral white/near‑black
- Avoid literal crypto coins or stock imagery; no text labels in the icon

Output:
- 256×256 PNG with transparent background
- Also produce a 128×128 variant
- Provide an SVG master with clean, grouped layers

Accessibility:
- Test legibility at 32×32; ensure forms remain distinguishable
- Use sufficient contrast AA/AAA where applicable
"""

---

## Prompt 2 — Plugin Banner (1544×500)
Copy the full block to Grok4:

"""
Create a WordPress.org plugin banner at 1544×500 for “WP‑Dapp,” a plugin that publishes WordPress posts to the Hive blockchain via Hive Keychain.

Composition:
- Left: abstract WordPress circle motif
- Right: abstract Hive hex motif
- Between them: a clean, subtle “bridge” or linking arc suggesting secure publishing
- Center/overall: modern, minimal theme; no text required (WP.org overlays title)

Style:
- Flat/semi‑flat; gentle gradient background ok (cool blue → subtle neutral)
- Use WordPress blues and Hive red accents; keep the red minimal, as accent
- Include faint, tasteful circuit or node pattern in the background at low opacity (5–10%)

Output:
- 1544×500 PNG and an SVG master
- Provide a scaled 772×250 PNG crop that maintains composition

Guidelines:
- Keep safe area clear near edges; WP.org may crop slightly
- Ensure iconography remains readable on both light and dark screens
"""

---

## Prompt 3 — Alt Concept (Monoline Outline)
Copy the full block to Grok4:

"""
Alternative direction: monoline outline style. Draw a single continuous line forming:
- A WordPress “W” circle on the left
- A Hive-inspired hex pattern on the right
- One continuous line connects them (bridge/flow) through the center

Style constraints:
- Single stroke weight, scalable SVG; export high‑res PNGs
- Minimalist, technical aesthetic; ensure line contrast vs. background
- Palette limited to WordPress blue for line, with a small Hive red accent at the bridge

Deliverables:
- Icon 256×256 PNG + SVG
- Banner 1544×500 PNG + SVG, plus 772×250 PNG crop
"""

---

## Handoff & Filing
- Save assets in `assets/wporg/` with names:
  - `icon-256.png`, `icon-128.png`, `icon.svg`
  - `banner-1544x500.png`, `banner-772x250.png`, `banner.svg`
- Verify transparency for icons; banners may use solid/gradient backgrounds
- Run contrast check and preview at small sizes

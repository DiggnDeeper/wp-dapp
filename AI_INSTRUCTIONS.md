# AI Instructions for wp-dapp

Purpose: Provide clear boundaries and workflows for AI-assisted changes in this repository so work remains safe, maintainable, and consistent.

## Project goals (north star)
- Ship a reliable, WordPress-native integration with Hive that works on common shared hosting.
- Make the Hive comments experience first-class (Hive‑only Display + optional mirroring + smooth reply UI).
- Keep operational risk low (no private keys stored; signing via Hive Keychain only).

## Out of bounds
- Do not introduce private key storage or server-side signing.
- Do not add hidden tracking or phone-home behavior.
- Do not modify production build/deploy secrets.

## Roles
- Senior/Tech Lead by default: propose design, call out risks, and explain trade-offs.
- Product partner: write acceptance criteria and keep scope tight.
- DevOps/Release: versioning, baselines, tagging, and hotfix protocols.

## Editing rules
- Preserve existing indentation and formatting style; do not reformat unrelated code.
- Prefer minimal diffs focused on the task at hand.
- Add necessary imports/dependencies when writing new code.
- For docs, keep tone concise and actionable; add links to relevant files.

## Code style summary
- PHP: WordPress Coding Standards via PHPCS.
- JS/CSS: Prettier formatting; ESLint (basic) where applicable.
- Naming: descriptive, full words; avoid 1–2 char identifiers.
- Control flow: early returns, explicit error handling; avoid catch-and-ignore.

## Commit policy
- Conventional Commits: feat/fix/docs/chore/refactor/test/build.
- Small, focused commits with clear intent.
- Reference impacted files or features in the body when helpful.

## Branching & releases
- main: stable.
- dev: next.
- feature/*: scoped changes merged into dev, then to main after passing checks.
- Tag baselines (e.g., baseline-YYYYMMDD-<shortsha>) and releases (vX.Y.Z).

## Review checklist (apply before merge)
- Lints pass (PHPCS for PHP, Prettier for JS/CSS).
- No breaking changes to settings or data without docs migration notes.
- User-facing strings i18n-wrapped and localized to JS when needed.
- Accessibility touchpoints covered (focus, aria-labels, keyboard paths).
- Updated docs: README, USER_GUIDE, BASELINE, ROADMAP as relevant.

## Baselines
- Use BASELINE.md to pin known-good functionality for comments and publish flows.
- Record anchor commit, environment, acceptance checks, and rollback notes.

## After shipping
- Update CHANGELOG.md and readme.txt plugin changelog when appropriate.
- Mark completed items in FEATURE_CHECKLIST.md.
- Consider a short postmortem for non-trivial changes.



# Contributing to WP‑Dapp

Thanks for your interest in contributing!

## Development
- WordPress Coding Standards via PHPCS
- Target: PHP 7.4+ (align with WP.org requirements)
- Branches: feature/*, fix/*; PRs into `main`
- Keep PRs small and focused

## Commit messages
- Conventional short prefix: fix:, feat:, docs:, chore:

## Testing
- Include reproduction steps / test plan in PRs
- Add unit tests when adding logic; smoke-test in a stock WP site

## Security
- Validate user capabilities and nonces for all actions
- Escape output, sanitize input
- Avoid storing secrets/keys server-side; rely on Hive Keychain

## Docs
- Update README/USER_GUIDE for user-visible changes
- Add CHANGELOG entry for notable changes

## Community
- Use Discussions for Q&A
- Be respectful; follow the Code of Conduct

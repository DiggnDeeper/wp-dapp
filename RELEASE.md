# Release Process

Semantic versioning: MAJOR.MINOR.PATCH

## Branching
- `main`: release branch
- Feature branches via PRs; squash-merge

## Pre-release checklist
- [ ] PHPCS clean
- [ ] Smoke-tested on latest WordPress
- [ ] Readme assets/screenshots up to date
- [ ] CHANGELOG updated

## Tag and publish
```bash
git switch main
git pull --rebase
npm run build || true  # if applicable
git tag vX.Y.Z -m "Release vX.Y.Z"
git push origin vX.Y.Z
```

## WordPress.org (when applicable)
- Ensure `readme.txt` meets WP.org spec
- Use SVN deploy or GitHub Actions deploy

## Post-release
- Announce in Discussions/Hive
- Triage any regressions

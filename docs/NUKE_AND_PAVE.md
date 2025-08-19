# Nuke and Pave (Recovery Playbook)

This document describes how to reset your local wp-dapp plugin environment to a clean, known-good state while preserving your work.

## Before you begin (backups)
- Zip the plugin directory: `wp-dapp/`
- Optionally export site content/settings from WordPress admin (Tools → Export)
- If you track the plugin with Nextcloud, pause sync during the reset

## Reset steps
1. Remove the existing plugin directory from your WordPress `wp-content/plugins/` path
2. Reinstall from your working repo or latest release zip
3. Activate the plugin in WordPress admin
4. Verify settings on Settings → WP‑Dapp
5. Run a simple smoke test: open a post, load comments, and trigger a Keychain reply (cancel is fine)

## Restore to a baseline
- Check `BASELINE.md` for the latest anchor commit and acceptance checklist
- Ensure your working tree matches the baseline commit, or tag and document a new baseline before proceeding

## Notes
- Avoid syncing large dependency folders in Nextcloud (`node_modules`, caches, vendor)
- Tag a git baseline (e.g., `baseline-YYYYMMDD-<shortsha>`) before major refactors
- Keep this playbook updated whenever the setup process changes

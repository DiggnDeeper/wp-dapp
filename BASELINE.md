# Working Baseline: Hive Comments & Reply UI

Date: 2024-10-15

This document captures a stable, known-good state of the Hive comments and reply experience on the front end. Use this as a reference point when making future changes.

## Frontend Reply Flow (Username-only)
- The reply UI does not have a separate "connect" step.
- Users type their Hive username in the form when not yet connected.
- On Submit, Hive Keychain opens to broadcast the comment.
- After the first successful broadcast, the username is remembered (localStorage + sessionStorage) and the UI shows:
  - Status chip: "Connected"
  - Text: "Connected as: <username>"
- Users can change accounts inline via a "Change" action, which toggles an input and Save/Cancel.

## Immediate Sync & DOM Refresh
- After a successful broadcast, the plugin triggers an AJAX sync of Hive comments for the current post.
- Once sync completes, the plugin fetches rendered comments HTML via AJAX and replaces the `.wpdapp-hive-comments` block.

## Accessibility & i18n
- Textarea gets focus when the form opens; it includes an aria-label.
- All UI strings are passed through translation functions and localized to JS.

## Settings Used
- `hive_only_mode` (Hive‑only Display): hides the WP comment form/template and shows the Hive thread on posts.
- `enable_comment_sync` (Enable Mirroring): optional; when on, copies Hive replies into native WP comments. When off, a one‑time Import button appears in the block for convenience.
- `show_reply_buttons`: controls whether in‑site Keychain reply buttons appear (always on in Hive‑only Display).
- `hive_frontend`: determines the link base (PeakD, Hive.blog, Ecency).
- `hive_max_thread_depth`: limits rendered nesting for both mirrored comments and live Hive thread.

## Files Involved
- `assets/js/hive-comment.js`: Reply UI logic, Keychain broadcast, sync/import, DOM refresh, inline account switcher.
- `includes/class-frontend.php`: Script/CSS enqueue + localization; comments rendering; shortcode; live Hive rendering in Hive‑only Display.
- `includes/class-ajax-handler.php`: Sync endpoint; supports one‑time Import when mirroring is off.
- `assets/css/style.css`: Styles for comments, reply UI, status chip, responsive tweaks.

## Notes
- Keychain is required only at broadcast time. The form still opens and guides the user if Keychain is missing.
- In Hive‑only Display, the block always shows comments: from WP if mirrored, or directly from Hive if not.
- Throttling prevents duplicate sync requests while one is running.



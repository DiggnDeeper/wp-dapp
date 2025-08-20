## WP‑DAPP Developer Overview

Audience: contributors to the `wp-dapp` plugin. This is a living map of key files, flows, and settings.


### Core flows
- Render Hive thread on posts
  - `includes/class-frontend.php`
    - Shortcode `wpdapp_hive_comments` builds the thread UI from mirrored WP comments or live Hive (Hive‑only display).

    - Enqueues `assets/js/hive-comment.js` and localizes AJAX endpoints and i18n.
    - Optional notice appended when WP comments are closed but mirroring is enabled.
- Reply from site using Hive Keychain
  - `assets/js/hive-comment.js`
    - Username is remembered (localStorage/sessionStorage). Inline status chip shows Connected/Not connected and supports Change → Save.

    - On submit: calls `hive_keychain.requestBroadcast` with a `comment` op → on success triggers AJAX sync → re-renders comments block.

- Mirror Hive replies into WP comments
  - `includes/class-comment-sync.php`
    - `sync_post_comments($post_id, $auto_approve)` imports replies, deduplicates via meta`_wpdapp_hive_comment_key`, maps parent/child, and sets approved based on settings.

    - Adds a 15‑minute cron (`wpdapp_every_15_minutes`) and a `cron_sync_all` walker for recent posts.

- Prepare post for publishing to Hive (Keychain handles the broadcast)
  - `includes/class-publish-handler.php`
    - `format_content_for_hive()` cleans WP HTML, strips block comments, normalizes tags, limits elements via `wp_kses`, and appends "Originally published on …" footer.

  - `includes/class-hive-api.php`
    - `prepare_post_data()` assembles `author`, `permlink`, `title`, `body`, `tags`, and `beneficiaries` for Keychain broadcasting.


### AJAX endpoints
- `includes/class-ajax-handler.php`
  - `wpdapp_sync_comments` (public): nonce `wpdapp_frontend_sync`; validates post is published and Hive‑published; runs `WP_Dapp_Comment_Sync`.

  - `wpdapp_render_hive_comments` (public): returns freshly rendered HTML of the comments block (used after sync and reply post).

  - `wpdapp_prepare_post`, `wpdapp_update_post_meta`: admin‑only helpers for publish flow.

### Settings
- `includes/class-settings-page.php` stores options in `wpdapp_options`:
  - Account: `hive_account`
  - Beneficiaries: `enable_default_beneficiary`, `default_beneficiary_account`, `default_beneficiary_weight` (stored as integer basis points ×10)

  - Post defaults: `default_tags`
  - Comment sync / display: `enable_comment_sync`, `auto_approve_comments`, `hive_only_mode`, `show_reply_buttons`, `hive_frontend`, `hive_max_thread_depth`

  - Advanced: `hive_api_node`

### Templates and assets
- Frontend CSS: `assets/css/style.css`
- Admin CSS: `assets/css/admin-styles.css`
- Frontend JS: `assets/js/hive-comment.js`
- Admin JS: `assets/js/keychain-integration.js`
- Empty comments template: `includes/templates/empty-comments.php`

### Security notes
- All AJAX endpoints verify nonces and permissions where applicable; public endpoints use a dedicated frontend nonce.

- Always escape output in renderers; sanitize all incoming data.
- Do not store private keys server‑side; Hive writes occur via user Keychain.

### MCP tooling (local only)
- Filesystem: operate within `/home/scott/wp-dapp`.
- GitHub: create issues/PRs and read repo content using `GITHUB_TOKEN`.
- Puppeteer: exercise Chrome reply flow; verify Keychain popup + DOM refresh.
- Hive server: for read ops and optional scripted writes with test creds.

### Quick dev tasks
- Re‑render the comments block: POST `wpdapp_render_hive_comments`.
- Import replies now: POST `wpdapp_sync_comments` (force=1 allows a one‑time import when mirroring is off).

- Limit thread depth: set `hive_max_thread_depth` (1–10).

### Release checklist (summary)
- Update plugin header (`wp-dapp.php`), `readme.txt` Stable tag, and `CHANGELOG.md`.
- Verify settings UI strings and user guide.
- Smoke test: reply flow in Chrome + Keychain; mirroring; Hive‑only display; accessibility basics.

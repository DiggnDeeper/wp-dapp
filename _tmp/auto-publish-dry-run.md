# Auto-Publish Dry Run: Mark, Review, Approve with Keychain

I’m testing WP‑Dapp’s Auto‑Publish workflow today. I enabled Auto‑Publish in Settings → WP‑Dapp and saved. After publishing this post in WordPress, the plugin marked it “Ready for Auto‑Publish,” showed an editor notice, and highlighted the Publish to Hive box. That’s the cue to finish the job: click Publish to Hive and approve in Hive Keychain.

For this test I’m checking a few things:
- Tags: pulled from categories, post tags, and default tags; normalized and capped at five. The first tag should be the parent permlink on Hive.
- Beneficiaries: custom entries from the meta box plus my default, respecting the percentage limits.
- UX: clear “ready” indicator, accurate status messages, and a working link to view the post on Hive after success.
- Safety: turning Auto‑Publish off should restore a fully manual workflow; missing account settings should produce a clear error rather than attempting to publish.
- Idempotence: editing an already published post should not trigger a new broadcast.

If you’re reading this on Hive, Keychain approved and the broadcast succeeded. If something looks off (tags, formatting, beneficiaries), I’ll tweak the settings and try again. This is a dry run to make sure Auto‑Publish streamlines the flow without sacrificing control.



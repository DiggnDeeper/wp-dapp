# WP-Dapp Feature Checklist

This checklist tracks improvements for the Hive comments and publishing features. It's prioritized from high to low. Last updated: 2025-08-19

## High Priority (0.9 scope)
- [x] **Improve Username Handling in Reply Form**  
  Replace prompt() with Keychain-based login to auto-detect username. Add "Connect with Keychain" button.  
  *Effort: Medium* | *Status: Completed*

- [x] **Add Immediate Sync-Back After Posting to Hive**  
  Trigger AJAX sync after successful Keychain post to show new comment immediately.  
  *Effort: Medium* | *Status: Completed*

- [x] **Enhance Error Handling and Feedback in Reply Form**  
  Add loading states, in-form messages, and handle common errors gracefully.  
  *Effort: Low* | *Status: Completed*

- [ ] **Reply UX Polish**  
  Add robust retry UX, error toasts, and smoother loading transitions.  
  *Effort: Medium* | *Status: Planned*

- [ ] **Background Sync Reliability**  
  Retries, debouncing, and clear progress indicators to avoid duplicate syncs.  
  *Effort: Medium* | *Status: Planned*

- [ ] **First‑run Onboarding Wizard**  
  Guide through Hive-only vs mirroring, thread depth, and frontend selection.  
  *Effort: Medium* | *Status: Planned*

## Medium Priority
- [x] **Consolidate and Improve Footer Text**  
  Merge redundant messages into a single clear notice.  
  *Effort: Low* | *Status: Completed*

- [x] **Add Setting for Hive Frontend Choice**  
  Allow choosing base URL for Hive links (e.g., PeakD vs. Hive.blog).  
  *Effort: Medium* | *Status: Completed*

- [x] **Refresh Comments DOM After Sync**  
  After successful sync, fetch rendered comments HTML via AJAX and replace the `.wpdapp-hive-comments` block.  
  *Effort: Medium* | *Status: Completed*

- [x] **Add Setting for Max Thread Depth**  
  Expose max nesting level (default 4) in settings; renderer uses this value.  
  *Effort: Low* | *Status: Completed*

- [x] **Add Setting to Show/Hide Reply Buttons**  
  Toggle between Keychain reply UI vs link-only mode per site preference.  
  *Effort: Medium* | *Status: Completed*

## Low Priority
- [x] **Handle Deep Threading Gracefully**  
  Limit indentation and add links for complex threads.  
  *Effort: Medium* | *Status: Completed*

- [x] **General Polish and Testing**  
  Add validation, mobile CSS, and test edge cases.  
  *Effort: High* | *Status: Completed*

- [x] **Internationalize New UI Text**  
  Wrap frontend strings and settings labels with translation functions and load textdomain.  
  *Effort: Low* | *Status: Completed*

- [x] **Accessibility Improvements (Reply UI)**  
  Keyboard focus management, ARIA labels/roles, and announce success/error messages.  
  *Effort: Low* | *Status: Completed*

- [x] **Throttle Front‑end Sync Requests**  
  Prevent duplicate sync clicks; debounce and display progress state.  
  *Effort: Low* | *Status: Completed*

## Notes
- Branch: dev (feature branches: `feat/*`)
- Test after each item: pull branch, clear caches, test on a post.
- Backup site before testing!

## Plan
- Next up (0.9): Reply UX polish, background sync reliability, onboarding wizard.
- Then: Dashboard widget and status indicators; beneficiary validation.

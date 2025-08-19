# WP-Dapp Feature Checklist: Hive Comment Synchronization

This checklist tracks improvements for the Hive comment sync feature. It's prioritized from high to low. Check off items as they're completed. Last updated: 2024-10-15

## High Priority
- [x] **Improve Username Handling in Reply Form**  
  Replace prompt() with Keychain-based login to auto-detect username. Add "Connect with Keychain" button.  
  *Effort: Medium* | *Status: Completed*

- [x] **Add Immediate Sync-Back After Posting to Hive**  
  Trigger AJAX sync after successful Keychain post to show new comment immediately.  
  *Effort: Medium* | *Status: Completed*

- [x] **Enhance Error Handling and Feedback in Reply Form**  
  Add loading states, in-form messages, and handle common errors gracefully.  
  *Effort: Low* | *Status: Completed*

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
- Branch: feature/hive-comment-sync
- Test after each item: Pull branch, clear cache, test on a post.
- Backup site before testing!

## Plan
- Next up: General Polish and Testing.
- Then: General Polish and Testing.
- All items completed!

# WP-Dapp Development Roadmap

## Project Overview
WP-Dapp is a WordPress plugin that integrates with the Hive blockchain, allowing WordPress users to publish their content to Hive directly from their WordPress dashboard. This enables creators to take advantage of blockchain technology and potentially earn cryptocurrency rewards without needing to manually cross-post content.

## Current Features
- WordPress integration for publishing to Hive via Hive Keychain (no private keys stored)
- Post meta box for Hive-specific settings (tags, beneficiaries)
- Tag conversion from WordPress categories/tags + default tags with dedupe and limits
- Beneficiary support with improved management UI
- Comments integration
  - Hive-only Display: render Hive thread without importing into WP
  - Optional mirroring: copy Hive replies into native WP comments
  - Inline reply UI: Keychain popup, remembered username, inline account switcher
  - Immediate sync-back and DOM refresh after posting
  - i18n + accessibility improvements; mobile-friendly styles
- Settings for Hive frontend choice (PeakD/Hive.blog/Ecency), max thread depth, and reply button visibility
- Simple update checker

## Planned Features and Improvements

### Phase 1: Core Functionality Improvements
- [x] Improve Hive API integration using proper Hive libraries
- [x] Add proper error handling and user feedback
- [x] Implement secure credential storage
- [x] Create post metadata box for Hive-specific settings
- [x] Add support for beneficiaries (including default diggndeeper.com)
- [x] Implement custom tag management
- [x] Add option to control when content is published to Hive (immediate vs. manual)

### Phase 2: User Experience Enhancements
- [ ] Develop dashboard widget showing Hive post status
- [ ] Add Hive post preview functionality
- [ ] Create post status indicators in post list
- [x] Implement Hive account connection verification (via Keychain)
- [ ] Add post update functionality with version tracking
  - [ ] UI controls for choosing between update and new post modes
  - [ ] Version history tracking for updated posts
  - [ ] Visual indicators for updated posts in WordPress admin
- [ ] Add statistics for published posts (views, earnings, etc.)
- [ ] Implement Markdown conversion options for better Hive formatting
- [x] Enhance beneficiary management UI with better visual feedback
- [ ] Add inline validation for beneficiary accounts
- [ ] Implement sortable beneficiary list
- [ ] Add beneficiary templates for quick reuse across posts

### Phase 3: Advanced Features
- [ ] Add support for Hive communities
- [ ] Moderation tools and anti-spam (candidate for Pro add-on)
- [ ] Add upvote/reward tracking
- [ ] Support for Hive Power delegation
- [ ] Implement Web3 wallet integration options
- [ ] Add scheduling features for optimal posting times
- [ ] Support for featured images and media synchronization
- [ ] Add option to retroactively publish old WordPress posts to Hive
- [ ] Implement batch operations for multiple posts

## Release 0.9 Scope (target)
- Reply UX polish: loading states, retries, error toasts
- Background sync reliability: retries, debouncing, clear progress
- First-run onboarding wizard
- Docs refresh: README, USER_GUIDE, BASELINE, readme.txt
- Simple CI: lint and smoke test

## Technical Stack
- PHP for WordPress integration
- JavaScript for admin + frontend UI enhancements
- Hive Keychain for signing/broadcast (no server-side key storage)
- WordPress Settings API for configuration
- WordPress Metadata API for post-specific settings
- jQuery for DOM manipulation and animation
- CSS for UI styling and visual feedback

## Git Workflow
We follow the GitHub Flow methodology:
1. Create feature branches from main
2. Commit changes with descriptive messages
3. Push branches to GitHub
4. Create Pull Requests for review
5. Merge to main after approval
6. Tag releases with semantic versioning (v1.0.0)

## Documentation Plan
- README.md - Project overview and quick start
- CONTRIBUTING.md - Guidelines for contributors
- User documentation in the WordPress admin interface
- Code documentation using PHPDoc standards
- Inline comments for complex logic
- Detailed change logs for upgrades

## Next Development Focus
1. **Reply UX polish**: loading states, error toasts, and smooth retries
2. **Background sync reliability**: robust retry/debounce, clear progress
3. **Onboarding wizard**: first-run guided setup for comments settings

## Learning Resources for Development
- [WordPress Plugin Developer Handbook](https://developer.wordpress.org/plugins/)
- [Hive Developer Portal](https://developers.hive.io/)
- [PHP Programming](https://www.php.net/manual/en/)
- [Git & GitHub Basics](https://guides.github.com/introduction/git-handbook/)
- [jQuery Documentation](https://api.jquery.com/)
- [Modern CSS Techniques](https://developer.mozilla.org/en-US/docs/Web/CSS) 
# WP-Dapp Development Roadmap

## Project Overview
WP-Dapp is a WordPress plugin that integrates with the Hive blockchain, allowing WordPress users to publish their content to Hive directly from their WordPress dashboard. This enables creators to take advantage of blockchain technology and potentially earn cryptocurrency rewards without needing to manually cross-post content.

## Current Features
- Basic WordPress integration structure
- Settings page for Hive credentials
- Simple post publishing to Hive on WordPress post publish
- Tag conversion from WordPress to Hive
- Custom tag management
- Support for beneficiaries including default diggndeeper.com beneficiary
- Post metadata box for Hive-specific settings
- Control when content is published to Hive (immediate vs. opt-out per post)
- Secure credential storage with encryption
- Credential verification
- Enhanced UX for beneficiary management with visual feedback

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
- [x] Implement Hive account connection verification
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
- [ ] Implement comment synchronization
- [ ] Add upvote/reward tracking
- [ ] Support for Hive Power delegation
- [ ] Implement Web3 wallet integration options
- [ ] Add scheduling features for optimal posting times
- [ ] Support for featured images and media synchronization
- [ ] Add option to retroactively publish old WordPress posts to Hive
- [ ] Implement batch operations for multiple posts

## Technical Stack
- PHP for WordPress integration
- JavaScript for admin UI enhancements
- Hive-js or similar libraries for blockchain interaction
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
1. **Enhanced Validation**: Add beneficiary account validation against Hive API
2. **UX Improvements**: Implement sorting and template functionality for beneficiaries
3. **Dashboard Integration**: Develop better post status indicators and dashboard widget

## Learning Resources for Development
- [WordPress Plugin Developer Handbook](https://developer.wordpress.org/plugins/)
- [Hive Developer Portal](https://developers.hive.io/)
- [PHP Programming](https://www.php.net/manual/en/)
- [Git & GitHub Basics](https://guides.github.com/introduction/git-handbook/)
- [jQuery Documentation](https://api.jquery.com/)
- [Modern CSS Techniques](https://developer.mozilla.org/en-US/docs/Web/CSS) 
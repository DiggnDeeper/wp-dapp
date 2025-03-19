# WP-Dapp Plugin Changelog

This file documents all notable changes to the WP-Dapp WordPress plugin.

## [Unreleased]
### Added

### Changed

### Fixed
- Fixed Auto-Publish checkbox defaulting to enabled in settings
- Implemented multiple safeguards to ensure Auto-Publish is always disabled by default
- Updated documentation to clarify Auto-Publish is an opt-in feature
- Fixed default beneficiary account to use correct username (diggndeeper.com)

### Documentation

## [0.7.3] - 2024-03-08
### Added
- Multiple failsafe mechanisms for beneficiary management
- Auto-Publish feature that detects newly published posts and marks them for Hive publication
- UI indicators for posts ready for Auto-Publish
- WordPress hooks to detect post status changes for Auto-Publish

### Changed
- Complete UI overhaul for Hive publishing interface
- Improved settings page UI/UX with enhanced styling and interaction
- Optimized beneficiary UI for sidebar meta box
- Complete rebuild of beneficiary UI for maximum simplicity and reliability
- Simplified UI and resolved duplicate meta box issues
- Improved Hive meta box UI organization and clarity

### Fixed
- Fixed Default Beneficiary Percentage display issue in settings UI
- Fixed beneficiary row targeting and add button issues
- Fixed Remove button with direct inline handlers
- Fixed Add Beneficiary button functionality in post meta box
- Fixed Hive Keychain script loading issues
- Now using official Hive Keychain script for better compatibility
- Improved the regex pattern in `format_content_for_hive()` function to properly strip all Gutenberg block comments from content when publishing to Hive.
  - Previous regex only matched comments with specific spacing
  - New regex handles all variations including multiline comments

### Documentation
- Updated README with beneficiary UX improvements
- Updated README with recent bugfix information
- Updated development roadmap with recent progress
- Added Auto-Publish feature documentation
- Created comprehensive USER_GUIDE document

## [0.7.2] - (Original Version)
- Plugin as provided by the original author

## Maintaining Your Changes During Updates

When updating the plugin from the original source, follow these steps to preserve your custom changes:

1. **Before updating:**
   - Create a backup of your customized files
   - Document which files and functions have custom changes (this changelog helps)

2. **After updating:**
   - Compare the new version's files with your customized files
   - Re-apply your changes carefully, making sure they're compatible with the new version

3. **Key modified files:**
   - `includes/class-publish-handler.php` - Modified regex for Gutenberg block comment stripping
   - `includes/class-settings-page.php` - Fixed beneficiary percentage display and improved UI
   - `includes/class-post-meta.php` - Improved beneficiary management and UI

4. **Testing after updates:**
   - Always test content publishing after re-applying changes
   - Verify that Gutenberg block comments are properly stripped
   - Test with various content formats (paragraphs, lists, images, etc.)
   - Verify beneficiary management works correctly

## Additional Recommendations

- Consider creating a child plugin that extends the functionality of wp-dapp instead of modifying core files directly
- Contribute your changes back to the original project if possible
- Use version control to track all modifications 
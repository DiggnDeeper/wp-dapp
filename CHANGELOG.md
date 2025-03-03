# WP-Dapp Plugin Changelog

This file documents all notable changes to the WP-Dapp WordPress plugin.

## [Unreleased]
### Fixed
- Improved the regex pattern in `format_content_for_hive()` function to properly strip all Gutenberg block comments from content when publishing to Hive.
  - Previous regex only matched comments with specific spacing
  - New regex handles all variations including multiline comments

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

4. **Testing after updates:**
   - Always test content publishing after re-applying changes
   - Verify that Gutenberg block comments are properly stripped
   - Test with various content formats (paragraphs, lists, images, etc.)

## Additional Recommendations

- Consider creating a child plugin that extends the functionality of wp-dapp instead of modifying core files directly
- Contribute your changes back to the original project if possible
- Use version control to track all modifications 
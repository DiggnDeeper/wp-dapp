# WP-Dapp: Hive Integration

A WordPress plugin that seamlessly integrates with the Hive blockchain, allowing you to publish content from WordPress to Hive directly using Hive Keychain.

**Version: 0.7.3**

## Features

- **Hive Keychain Authentication**: Securely authenticate with your Hive account using the Hive Keychain browser extension (no private keys stored)
- **Post to Hive**: Publish WordPress posts to the Hive blockchain with a single click
- **Auto-Publish**: Optionally enable automatic detection of newly published WordPress posts for Hive publishing (disabled by default for security)
- **Beneficiary Support**: Add beneficiaries to share rewards with other Hive accounts
- **WordPress Tag Integration**: Automatically converts WordPress categories and tags to Hive tags
- **Default Tags**: Set default tags to include in all your Hive posts
- **Custom API Node**: Optionally specify a custom Hive API node
- **Post Status Tracking**: Track which posts have been published to Hive
- **Attribution**: Automatically adds attribution link back to your WordPress site

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher
- [Hive Keychain browser extension](https://hive-keychain.com/) installed

## Installation

1. Download the latest release zip file
2. In your WordPress admin, go to Plugins > Add New > Upload Plugin
3. Upload the zip file and activate the plugin
4. Go to Settings > WP-Dapp to configure your Hive account

## Setup

1. **Install Hive Keychain**: Install the [Hive Keychain browser extension](https://hive-keychain.com/) for your browser
2. **Configure Hive Account**: Go to Settings > WP-Dapp and enter your Hive username
3. **Verify Account**: Click the "Verify with Keychain" button to authenticate your account
4. **Configure Beneficiaries**: (Optional) Set up default beneficiaries to receive a share of post rewards
5. **Auto-Publish Setting**: (Optional) Enable the Auto-Publish feature in the plugin settings

## How to Use

### Manual Publishing
1. **Create/Edit a Post**: Write a post in WordPress as usual
2. **Publish to WordPress**: Publish or update your post in WordPress
3. **Publish to Hive**: On the post edit screen, find the "Publish to Hive" meta box and click "Publish to Hive with Keychain"
4. **Approve Transaction**: Confirm the transaction in the Hive Keychain popup
5. **View on Hive**: After successful publication, click the "View on Hive" link to see your post on Hive

### Auto-Publishing
1. **Enable Auto-Publish**: Go to Settings > WP-Dapp and check the "Auto-Publish" option (this feature is disabled by default for security reasons)
2. **Create and Publish Posts**: When you publish a new post in WordPress, it will be automatically marked for Hive publishing
3. **Complete the Publication**: After publishing in WordPress, you'll see a notification in the post editor. Click the "Publish to Hive" button to complete the process
4. **Approve Transaction**: Confirm the transaction in the Hive Keychain popup

## Hive Keychain Integration

This plugin uses Hive Keychain for secure authentication and transaction signing. Keychain is a browser extension that keeps your Hive private keys secure while allowing authorized applications to request operations.

### Benefits of Keychain

- **Enhanced Security**: Your private keys never leave your browser and are never stored by the plugin
- **Easy Authentication**: One-click verification of your Hive account
- **Transaction Approval**: Review and approve each publication to Hive
- **No Password Entry**: Publish without entering your private keys

### How It Works

1. The plugin detects if Hive Keychain is installed in your browser
2. When publishing, the plugin prepares your post data (title, content, tags, beneficiaries)
3. Keychain presents a popup asking for your approval to publish
4. After approval, the transaction is signed by Keychain and broadcast to the Hive blockchain
5. The post metadata is updated in WordPress to track the published status

## Tag System

The plugin handles tags from multiple sources:

1. **WordPress Categories**: Automatically converted to Hive tags
2. **WordPress Tags**: Automatically converted to Hive tags
3. **Default Tags**: Global tags set in the plugin settings

Tags are processed as follows:
- All tags from different sources are combined
- Duplicates are removed
- Tags are limited to 5 (Hive's maximum)
- The first tag becomes the "parent_permlink" in Hive (main category)
- If no tags are available, 'blog' is used as a default

## Beneficiary System

The plugin supports adding beneficiaries to receive a share of post rewards:

1. **Default Beneficiary**: Configure a default beneficiary in the plugin settings
2. **Per-Post Beneficiaries**: Add specific beneficiaries for individual posts
3. **Weight Configuration**: Set the percentage of rewards each beneficiary receives

## Troubleshooting

### Keychain Not Detected
- Make sure you have installed the [Hive Keychain browser extension](https://hive-keychain.com/)
- Try refreshing the page after installing Keychain
- Ensure you're using a supported browser (Chrome, Firefox, Brave, Edge)

### Authentication Failed
- Check that you've entered the correct Hive username
- Ensure that Keychain is unlocked
- Try refreshing the page and authenticating again

### Publishing Failed
- Check the error message displayed in the publishing box
- Ensure your post has a title and content
- Verify that you've authorized the transaction in Keychain
- Check your internet connection and try again

## Support & Contributions

Please report bugs or suggest features through the [GitHub repository](https://github.com/DiggnDeeper/wp-dapp).

## License

This plugin is licensed under the MIT License.

## Recent Updates

- Improved beneficiary management UX with smooth animations and better feedback
- Fixed the "Remove" button functionality with proper event handling
- Fixed Hive Keychain script loading issues with official source
- Fixed the "Add Beneficiary" button functionality in the post editor
- Improved JavaScript event handling for better reliability
- Added better error handling and debugging support
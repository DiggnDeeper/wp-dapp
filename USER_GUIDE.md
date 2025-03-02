# WP-Dapp User Guide

## Introduction

WP-Dapp is a WordPress plugin that enables you to automatically publish your WordPress content to the Hive blockchain. This integration creates a bridge between your WordPress site and the Hive ecosystem, allowing you to reach new audiences and potentially earn cryptocurrency rewards.

## Table of Contents

1. [Installation](#installation)
2. [Configuration](#configuration)
3. [Publishing to Hive](#publishing-to-hive)
4. [Tag Management](#tag-management)
5. [Beneficiaries](#beneficiaries)
6. [Verifying Publication Status](#verifying-publication-status)
7. [Troubleshooting](#troubleshooting)
8. [Security Information](#security-information)
9. [Frequently Asked Questions](#frequently-asked-questions)

## Installation

1. Download the latest release from the [GitHub repository](https://github.com/DiggnDeeper/wp-dapp)
2. In your WordPress admin dashboard, go to **Plugins > Add New > Upload Plugin**
3. Choose the downloaded ZIP file and click **Install Now**
4. After installation, click **Activate Plugin**

## Configuration

### Setting Up Your Hive Account

1. Go to **Settings > WP-Dapp** in your WordPress admin dashboard
2. Enter your Hive account name (username)
3. Enter your Hive private posting key
   - This key is used to sign transactions on your behalf
   - The plugin securely encrypts this key in your WordPress database
4. Click the **Verify Credentials** button to ensure your credentials are valid
5. Click **Save Changes**

### Publishing Options

1. **Default Tags**: Set default tags that will be added to all Hive posts
2. **Enable Custom Tags**: Toggle whether default tags should be used
3. **Default Beneficiary**: Set up a default beneficiary for your posts
   - By default, diggndeeper receives 1% as the plugin developer
   - You can adjust or disable this in the settings

### Advanced Settings

1. **Secure Storage**: Encrypts your private key (recommended)
2. **Delete Data on Uninstall**: Choose whether to remove all data when uninstalling

## Publishing to Hive

When you publish a WordPress post, it will automatically be published to Hive if:

1. You have properly configured your Hive credentials
2. The post is set to "Publish to Hive" in the post's meta box
3. The post has at least one valid tag (WordPress category, tag, or custom tag)

### Per-Post Settings

Each post has a **Hive Publishing Settings** meta box with the following options:

1. **Publish to Hive**: Toggle whether this specific post should be published to Hive
2. **Custom Tags**: Add Hive-specific tags for this post (comma-separated)
3. **Beneficiaries**: Add additional beneficiaries for this specific post
4. **Publication Status**: Shows whether the post has been published to Hive

## Tag Management

Tags are crucial for Hive as they determine how your content is categorized and discovered.

### Tag Sources

WP-Dapp combines tags from multiple sources:

1. **WordPress Categories**: Automatically converted to Hive tags
2. **WordPress Tags**: Automatically converted to Hive tags
3. **Custom Tags**: Added per post in the Hive Publishing Settings meta box
4. **Default Tags**: Global tags set in the plugin settings

### Tag Limitations

Hive has specific tag requirements:

- Maximum of 5 tags per post
- Tags must be lowercase letters, numbers, or hyphens
- No spaces or special characters allowed
- At least 1 tag is required

The plugin automatically handles these limitations by:
- Combining all tag sources
- Removing duplicates
- Limiting to 5 tags maximum
- Converting tags to valid Hive format
- Using 'blog' as a default tag if none are provided

### Tag Priority

When limiting to 5 tags, the plugin prioritizes in this order:
1. WordPress Categories
2. WordPress Tags
3. Custom Tags from meta box
4. Default tags from settings

## Beneficiaries

Beneficiaries receive a percentage of the rewards earned by your posts on Hive.

### Default Beneficiary

The plugin settings include a default beneficiary:
- **diggndeeper**: Plugin developer (default 1%)
- You can adjust or disable this in the settings

### Per-Post Beneficiaries

For each post, you can:
1. Add custom beneficiaries in the Hive Publishing Settings meta box
2. Specify the account name and percentage for each beneficiary
3. Add multiple beneficiaries as needed

## Verifying Publication Status

There are several ways to verify if your content was published to Hive:

### Post Meta Box

The Hive Publishing Settings meta box shows the publication status:
- **Published**: Shows a success message with a link to view on Hive
- **Error**: Shows an error message explaining what went wrong

### Publication Verification Tool

The plugin includes a verification tool:
1. Go to **Settings > WP-Dapp**
2. Scroll to the **Publishing Verification** section
3. Click **Check Hive Publication Status**
4. View a list of posts with their Hive publication status

## Troubleshooting

### Common Issues

**Post Not Publishing to Hive**

1. **Check Credentials**: Verify your Hive account and private key are correct
2. **Check Post Settings**: Ensure "Publish to Hive" is enabled for the post
3. **Check Tags**: Make sure the post has at least one valid tag
4. **Check Error Messages**: Look for error messages in the Hive Status section

**Tag Issues**

1. **Not Enough Tags**: Add WordPress categories, tags, or custom tags
2. **Too Many Tags**: Only the first 5 tags will be used
3. **Invalid Tags**: Tags are automatically converted to valid format

**Beneficiary Issues**

1. **Invalid Account**: Ensure beneficiary accounts exist on Hive
2. **Invalid Percentage**: Percentages must be between 1% and 100%

### Forcing Re-Publication

To force a post to be re-published to Hive:

1. Edit the post in WordPress
2. Delete these meta fields (using a custom fields plugin or developer tools):
   - `_hive_published`
   - `_hive_permlink`
   - `_hive_author`
   - `_hive_publish_error`
3. Make sure "Publish to Hive" is checked
4. Update the post to trigger the publishing process again

## Security Information

WP-Dapp takes security seriously:

1. **Private Key Encryption**: Your Hive private key is encrypted before storage
2. **Minimal Permissions**: The plugin only requires your posting key, not active or owner keys
3. **No External Services**: Your keys are never sent to external services
4. **WordPress Security**: Follow standard WordPress security best practices

## Frequently Asked Questions

**Q: Is my private key secure?**
A: Yes, when secure storage is enabled, your private key is encrypted before being stored in the WordPress database.

**Q: Do I need a Hive account?**
A: Yes, you need a Hive account and its private posting key to use this plugin.

**Q: Will my WordPress content be exactly the same on Hive?**
A: The plugin formats your content for Hive and adds a footer with attribution to your WordPress site.

**Q: Can I earn cryptocurrency from my posts?**
A: Yes, content on Hive can earn HIVE tokens through upvotes from other users.

**Q: What happens if I update a post on WordPress?**
A: Currently, the plugin only publishes new posts, it doesn't update existing posts on Hive.

**Q: Can I disable the default beneficiary?**
A: Yes, you can disable the default beneficiary in the plugin settings.

---

For more information, visit [diggndeeper.com/wp-dapp](https://diggndeeper.com/wp-dapp/) or the [GitHub repository](https://github.com/DiggnDeeper/wp-dapp). 
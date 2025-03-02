# Contributing to WP-Dapp

Thank you for your interest in contributing to WP-Dapp! This document provides guidelines and instructions for contributing to this project.

## Code of Conduct

Please be respectful, inclusive, and considerate to other contributors. We aim to make this project a welcoming space for everyone.

## Getting Started

1. **Fork the Repository**: Start by forking the [WP-Dapp repository](https://github.com/DiggnDeeper/wp-dapp) to your own GitHub account.

2. **Clone Your Fork**: 
   ```bash
   git clone https://github.com/YOUR-USERNAME/wp-dapp.git
   cd wp-dapp
   ```

3. **Add Upstream Remote**:
   ```bash
   git remote add upstream https://github.com/DiggnDeeper/wp-dapp.git
   ```

4. **Create a Branch**:
   ```bash
   git checkout -b feature/your-feature-name
   ```

## Development Environment

### Prerequisites
- WordPress development environment
- PHP 7.2+
- Hive test account (for testing)

### Setting Up Development Environment
1. Install a local WordPress development environment (like LocalWP, XAMPP, or Docker)
2. Add the wp-dapp plugin to your WordPress plugins directory
3. Activate the plugin in WordPress admin
4. Configure with your test Hive account

## Coding Standards

- Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)
- Use PHPDoc comments for functions and classes
- Keep code modular and maintainable
- Write descriptive commit messages

## Testing

Before submitting a pull request:
1. Test your changes thoroughly
2. Ensure no PHP errors or warnings are generated
3. Check for WordPress compatibility
4. Test with different themes and plugins to ensure compatibility

## Pull Request Process

1. **Update Your Fork**:
   ```bash
   git fetch upstream
   git checkout main
   git merge upstream/main
   git checkout your-feature-branch
   git rebase main
   ```

2. **Push Your Changes**:
   ```bash
   git push origin your-feature-branch
   ```

3. **Submit a Pull Request**: 
   - Go to the original repository
   - Click "New Pull Request"
   - Choose "compare across forks"
   - Select your fork and branch
   - Fill in the PR template

4. **Code Review**:
   - Maintainers will review your code
   - Be responsive to feedback and make requested changes
   - Once approved, your PR will be merged

## Feature Requests and Bug Reports

- Use the GitHub Issues tab to submit feature requests or bug reports
- Clearly describe the issue or feature
- For bugs, include steps to reproduce, expected behavior, and actual behavior
- For features, explain the use case and benefits

## Documentation

- Update relevant documentation for your changes
- Add comments to your code
- Consider updating the README if your changes affect usage

## Commit Messages

Use clear and descriptive commit messages:
- Start with a verb in present tense (Add, Fix, Update, etc.)
- Keep the first line under 72 characters
- Add more detailed explanation in the commit body if needed

Example:
```
Add beneficiary support to post metadata box

- Adds UI for setting beneficiaries
- Implements storage in post meta
- Updates the Hive publishing process to include beneficiaries
```

## Resources

- [WordPress Plugin Developer Handbook](https://developer.wordpress.org/plugins/)
- [Hive Developer Portal](https://developers.hive.io/)
- [Git & GitHub Basics](https://guides.github.com/introduction/git-handbook/)

Thank you for contributing to WP-Dapp! 
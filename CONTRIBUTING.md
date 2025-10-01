# Contributing to OAuth Sprinkle

Thank you for your interest in contributing to the OAuth Sprinkle for UserFrosting 6! 

## How to Contribute

### Reporting Bugs

If you find a bug, please open an issue on GitHub with:

1. **Clear title** describing the issue
2. **Steps to reproduce** the problem
3. **Expected behavior** vs actual behavior
4. **Environment details**:
   - PHP version
   - UserFrosting version
   - OAuth provider(s) affected
   - Browser (if relevant)
5. **Error messages** or logs (if any)

### Suggesting Features

We welcome feature suggestions! Please open an issue with:

1. **Clear description** of the feature
2. **Use case**: Why is this feature needed?
3. **Proposed implementation** (if you have ideas)
4. **Examples** from other projects (if applicable)

### Pull Requests

#### Before You Start

1. Open an issue to discuss major changes
2. Check existing issues and PRs to avoid duplication
3. Fork the repository
4. Create a feature branch from `main`

#### Development Setup

```bash
# Clone your fork
git clone https://github.com/yourusername/sprinkle-oauth.git
cd sprinkle-oauth

# Install dependencies
composer install

# Create a feature branch
git checkout -b feature/your-feature-name
```

#### Code Guidelines

1. **Follow PSR-12** coding standards
2. **Add PHPDoc comments** to classes and methods
3. **Keep changes focused**: One feature/fix per PR
4. **Write clear commit messages**:
   ```
   Add support for GitHub OAuth provider
   
   - Add GitHub provider to OAuthService
   - Update configuration for GitHub credentials
   - Add GitHub button to login template
   ```

5. **Test your changes**:
   - Test with at least one OAuth provider
   - Verify existing functionality still works
   - Test edge cases

#### Code Structure

```
app/
├── src/
│   ├── Controller/       # HTTP controllers
│   ├── Database/         # Migrations
│   ├── Entity/           # Database models
│   ├── Repository/       # Data access layer
│   ├── Service/          # Business logic
│   └── ServicesProvider/ # DI container setup
├── config/               # Configuration files
└── locale/              # Translations
```

#### Adding a New OAuth Provider

To add a new provider (e.g., GitHub):

1. **Add OAuth client library** to `composer.json`:
   ```json
   "league/oauth2-github": "^3.0"
   ```

2. **Update OAuthService** (`app/src/Service/OAuthService.php`):
   ```php
   case 'github':
       return new GitHub($providerConfig);
   ```

3. **Add default scopes**:
   ```php
   'github' => ['user:email', 'read:user'],
   ```

4. **Update configuration** (`app/config/default.php`):
   ```php
   'github' => [
       'clientId' => getenv('OAUTH_GITHUB_CLIENT_ID') ?: '',
       'clientSecret' => getenv('OAUTH_GITHUB_CLIENT_SECRET') ?: '',
   ],
   ```

5. **Update templates** to add GitHub button

6. **Update documentation** (README, INSTALL.md)

#### Pull Request Process

1. **Update your fork**:
   ```bash
   git remote add upstream https://github.com/ssnukala/sprinkle-oauth.git
   git fetch upstream
   git rebase upstream/main
   ```

2. **Push your changes**:
   ```bash
   git push origin feature/your-feature-name
   ```

3. **Create Pull Request**:
   - Use a clear, descriptive title
   - Reference related issues (#123)
   - Describe what changes were made and why
   - Include testing steps
   - Add screenshots for UI changes

4. **Respond to feedback**:
   - Address review comments
   - Make requested changes
   - Push updates to your branch

### Testing

Currently, this project uses manual testing. Future improvements welcome:

- Unit tests for services
- Integration tests for OAuth flows
- Mock providers for testing

### Documentation

Good documentation helps everyone! You can contribute by:

- Improving existing documentation
- Adding examples
- Fixing typos
- Translating to other languages
- Creating tutorials or guides

### Translation

To add a new language:

1. Create directory: `app/locale/{locale}/`
2. Copy `en_US/oauth.php` to new locale
3. Translate all strings
4. Test with UserFrosting locale system

## Code of Conduct

### Our Standards

- **Be respectful** and inclusive
- **Be constructive** in criticism
- **Focus on what's best** for the community
- **Show empathy** towards others

### Unacceptable Behavior

- Harassment or discrimination
- Trolling or insulting comments
- Publishing others' private information
- Other unprofessional conduct

## Questions?

- Open an issue for questions about development
- Check existing issues and PRs first
- Join UserFrosting community for general help

## Recognition

Contributors will be recognized in:
- CHANGELOG.md
- GitHub contributors page
- Project documentation

Thank you for contributing! 🎉

# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2024-10-01

### Added
- Initial release of OAuth Sprinkle for UserFrosting 6
- Support for Google OAuth authentication
- Support for Facebook OAuth authentication
- Support for LinkedIn OAuth authentication
- Support for Microsoft OAuth authentication
- Auto-creation of UserFrosting accounts from OAuth authentication
- Multiple OAuth providers per user account
- OAuth login page with provider buttons
- User settings component for managing OAuth connections
- Database migration for oauth_connections table
- Comprehensive documentation and README
- Environment-based configuration
- CSRF protection via state parameter
- Secure token storage

### Security
- OAuth tokens are encrypted and hidden from API responses
- State parameter validation for CSRF protection
- Unique constraints to prevent duplicate connections
- Foreign key constraints for data integrity

[1.0.0]: https://github.com/ssnukala/sprinkle-oauth/releases/tag/v1.0.0

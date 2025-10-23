# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- **OAuthConnectionInterface**: New interface following UserFrosting 6 patterns (similar to `PersistenceInterface`)
  - Defines contract for OAuth connection models
  - Includes scopes: `notExpired()`, `forProvider()`, `joinUser()`
- **Comprehensive Documentation**: Added PERSISTENCE_INTEGRATION_ANALYSIS.md explaining architecture decisions
- **Enhanced PHPDoc Comments**: All classes and methods now have comprehensive documentation

### Changed
- **BREAKING**: `OAuthConnection` model now extends `UserFrosting\Sprinkle\Core\Database\Models\Model` (was `Illuminate\Database\Eloquent\Model`)
  - Implements `OAuthConnectionInterface` for type safety
  - Uses DI container for User relationship (`UserInterface`)
  - Added scopes: `notExpired()`, `forProvider()`, `joinUser()`
- **OAuthAuthenticator**: Enhanced with proper type declarations and DI injection
  - Uses `UserInterface` from DI container instead of hardcoded class names
  - All methods have comprehensive PHPDoc with proper type hints
  - Parameters use `array<string, mixed>` notation for better IDE support
- **OAuthConnectionRepository**: Updated with interface types and comprehensive documentation
  - All return types use `OAuthConnectionInterface` instead of concrete class
  - Added `Collection<int, OAuthConnectionInterface>` type hint for findByUserId
  - Enhanced method documentation explaining use cases
- **Service Providers**: Enhanced with proper DI configuration
  - `OAuthServicesProvider` now injects `UserInterface` into `OAuthAuthenticator`
  - Added comprehensive comments explaining each service registration
- **Migration**: Enhanced documentation with design rationale
  - Explains why `oauth_connections` is separate from `persistences` table
  - Added reference to architecture decision document
- **Routes**: Enhanced with comprehensive route documentation
- **README**: Added "Architecture" section explaining design decisions

### Documentation
- Updated .github/copilot-instructions.md with OAuth-specific patterns
- Added architecture decision documentation for separate OAuth table
- Enhanced all PHPDoc comments to follow UserFrosting 6 standards
- Added comprehensive code examples for scopes and interfaces

### Architecture Decision
- **Separate OAuth Table**: Maintains `oauth_connections` as separate table from `persistences`
  - Different purposes: OAuth linking vs session management
  - Different data models: OAuth needs provider, tokens, refresh tokens, user_data
  - Different lifecycles: long-term connections vs frequent token rotation
  - Multiple providers: users can link multiple OAuth providers (Google + Facebook + LinkedIn)
  - Follows UF6 pattern: separation of concerns (like roles, permissions, activities)

## [1.1.1] - 2025-01-XX

### Fixed
- **CRITICAL**: Fixed PHP 8.x compatibility issue by reverting Facebook OAuth from abandoned `facebook/graph-sdk` to maintained `league/oauth2-facebook`
  - `facebook/graph-sdk` v5.7 requires PHP ^5.4|^7.0 which is incompatible with PHP 8.x
  - `league/oauth2-facebook` v2.2 requires PHP >=7.3 and supports PHP 8.x
  - The `facebook/graph-sdk` package is officially abandoned by Facebook
- Updated OAuthService to use League OAuth2 client for Facebook authentication
- Added `minimum-stability: alpha` and `prefer-stable: true` to composer.json for UserFrosting 6 beta compatibility

### Changed
- Reverted Facebook OAuth implementation from `facebook/graph-sdk` back to `league/oauth2-facebook` for better PHP 8.x support and active maintenance
- **ARCHITECTURE**: Restructured folder organization to align with UserFrosting 6 conventions
  - Moved `Entity/` to `Database/Models/` to match pattern used in `userfrosting/sprinkle-core` and `userfrosting/sprinkle-admin`
  - Updated namespace from `UserFrosting\Sprinkle\OAuth\Entity` to `UserFrosting\Sprinkle\OAuth\Database\Models`
  - All imports and references updated accordingly
  - Documentation updated to reflect new structure
- Added comprehensive documentation on extending UserFrosting patterns (similar to PDOStorage pattern)

### Documentation
- Added "Extending UserFrosting Core Patterns" section to API.md
- Included examples of extending models, services, and repositories
- Documented folder structure conventions matching UserFrosting 6

## [1.1.0] - 2024-10-01

### Changed
- **BREAKING**: Replaced League OAuth packages with official vendor SDKs
  - `google/apiclient` for Google OAuth (official Google API PHP Client)
  - `facebook/graph-sdk` for Facebook OAuth (official Facebook SDK)
  - `microsoft/microsoft-graph` for Microsoft OAuth (official Microsoft Graph SDK)
  - Custom LinkedIn implementation using Guzzle (LinkedIn has no official PHP SDK)
- Updated OAuthService to work with official vendor APIs
- Improved token exchange and user info retrieval with native SDK methods

### Why This Change?
- Better long-term maintenance and support from vendors
- More reliable and up-to-date implementations
- Direct access to vendor features and improvements
- Reduced dependency on third-party wrappers

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

[1.1.1]: https://github.com/ssnukala/sprinkle-oauth/releases/tag/v1.1.1
[1.1.0]: https://github.com/ssnukala/sprinkle-oauth/releases/tag/v1.1.0
[1.0.0]: https://github.com/ssnukala/sprinkle-oauth/releases/tag/v1.0.0

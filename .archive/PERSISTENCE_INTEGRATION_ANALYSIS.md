# OAuth Sprinkle - Persistence Integration Analysis

## Executive Summary

This document analyzes the integration of OAuth functionality with UserFrosting 6's built-in Persistence mechanism. After thorough review, the recommendation is to **keep the separate oauth_connections table** but align it with UserFrosting 6 patterns and standards.

## Current Implementation Analysis

### OAuth Connections Table Structure
```sql
CREATE TABLE oauth_connections (
    id INT PRIMARY KEY,
    user_id INT NOT NULL,
    provider VARCHAR(50),           -- google, facebook, linkedin, microsoft
    provider_user_id VARCHAR(255),  -- OAuth provider's user ID
    access_token TEXT,              -- OAuth access token
    refresh_token TEXT NULLABLE,    -- OAuth refresh token
    expires_at TIMESTAMP NULLABLE,  -- Token expiration
    user_data JSON NULLABLE,        -- Provider user data
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE(user_id, provider, provider_user_id),
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
)
```

### UserFrosting Persistence Table Structure
```sql
CREATE TABLE persistences (
    id INT PRIMARY KEY,
    user_id INT NOT NULL,
    token CHAR(64),                 -- SHA-256 hashed session token
    persistent_token CHAR(64),      -- SHA-256 hashed remember-me token
    expires_at TIMESTAMP NULLABLE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX(user_id),
    INDEX(token),
    INDEX(persistent_token)
)
```

## Integration Options Evaluation

### Option 1: Use Persistence Table with Type Field (NOT RECOMMENDED)

**Approach:** Add a `type` field to persistence table to differentiate session vs OAuth tokens.

**Pros:**
- Single table for all authentication persistence
- Unified token expiration handling

**Cons:**
- ❌ **Breaks existing persistence functionality** - persistence table is designed for session tokens
- ❌ **Data structure mismatch** - OAuth needs provider, provider_user_id, refresh_token, user_data
- ❌ **Token format incompatibility** - persistence uses 64-char SHA-256 hashes, OAuth uses variable-length JWTs
- ❌ **Multiple providers per user** - OAuth allows multiple providers per user (Google + Facebook + LinkedIn)
- ❌ **Would require schema changes to core UserFrosting table** - violates separation of concerns
- ❌ **Breaks UserFrosting 6 patterns** - persistence is for session management, not OAuth connections

### Option 2: Polymorphic Relationship Pattern (NOT RECOMMENDED)

**Approach:** Create a polymorphic relationship where OAuth connections reference persistence records.

**Pros:**
- Maintains some relationship to persistence table

**Cons:**
- ❌ **Overcomplicated** - adds unnecessary abstraction
- ❌ **Doesn't solve the fundamental mismatch** - OAuth and session persistence are different concerns
- ❌ **Poor separation of concerns** - mixes authentication persistence with OAuth provider linking
- ❌ **Harder to maintain** - polymorphic relationships add complexity

### Option 3: Separate OAuth Table Following UF6 Patterns (RECOMMENDED ✅)

**Approach:** Keep separate `oauth_connections` table but ensure it follows UserFrosting 6 standards.

**Pros:**
- ✅ **Separation of concerns** - OAuth provider connections are distinct from session persistence
- ✅ **Follows UserFrosting patterns** - similar to how roles, permissions, activities are separate tables
- ✅ **Clean data model** - each table serves its specific purpose
- ✅ **No breaking changes** - doesn't affect existing persistence functionality
- ✅ **Extensible** - easy to add new OAuth-specific features
- ✅ **Multiple providers support** - natural support for linking multiple OAuth providers per user
- ✅ **Provider-specific data storage** - can store provider-specific user_data in JSON

**Implementation Requirements:**
1. Ensure OAuthConnection model extends UserFrosting's Model base class
2. Use proper interfaces (similar to PersistenceInterface)
3. Follow repository pattern properly
4. Implement proper service providers
5. Add proper documentation and PHPDoc comments
6. Follow PSR-12 coding standards
7. Use proper type declarations

## Recommended Implementation Plan

### Phase 1: Review and Enhance Current Implementation
1. ✅ Verify OAuthConnection model follows UF6 patterns
2. ✅ Ensure proper interface implementation
3. ✅ Review migration for UF6 standards compliance
4. ✅ Update repository to follow framework patterns
5. ✅ Enhance service providers

### Phase 2: Standards Compliance
1. Add OAuthConnectionInterface following PersistenceInterface pattern
2. Ensure proper PHPDoc comments on all methods
3. Add type declarations everywhere
4. Implement proper scopes (e.g., scopeNotExpired similar to Persistence)
5. Follow naming conventions

### Phase 3: Integration Points
1. Ensure proper relationship with User model
2. Add proper cascade delete rules
3. Implement token refresh mechanism
4. Add proper expiration handling
5. Security review for token storage

### Phase 4: Documentation
1. Update copilot-instructions.md with OAuth-specific patterns
2. Document the design decision to keep separate table
3. Add architecture documentation
4. Update README with technical details

## Why Keep Separate Tables?

### Different Purposes
- **Persistence Table**: Manages session tokens and remember-me functionality for UserFrosting authentication
- **OAuth Connections Table**: Links users to external OAuth provider accounts and stores OAuth-specific tokens

### Different Data Models
- **Persistence**: Simple token pairs with expiration
- **OAuth**: Provider, provider_user_id, access_token, refresh_token, provider-specific user data

### Different Lifecycles
- **Persistence**: Tokens expire and are recreated frequently during user sessions
- **OAuth**: Connections persist long-term, tokens are refreshed, provider links are maintained

### UserFrosting 6 Precedent
UserFrosting 6 already uses separate tables for related but distinct concerns:
- `users` - User accounts
- `persistences` - Session persistence
- `roles` - User roles
- `permissions` - Permissions
- `role_users` - Many-to-many mapping
- `activities` - Activity logging

OAuth connections follow this same pattern - they're a separate concern that relates to users.

## Comparison with sprinkle-crud6

After reviewing `ssnukala/sprinkle-crud6`:
- ✅ sprinkle-crud6 uses separate tables for CRUD entities
- ✅ Each entity has its own model, repository, and migration
- ✅ Follows same pattern: separate concerns in separate tables
- ✅ Uses proper service providers and DI
- ✅ Implements interfaces for consistency

The OAuth sprinkle should follow the same pattern.

## Code Quality Checklist

Current status of OAuth sprinkle against UF6 standards:

### Model (OAuthConnection.php)
- [x] Extends Illuminate\Database\Eloquent\Model (should extend UserFrosting Model)
- [x] Has proper fillable fields
- [x] Has proper casts
- [x] Has hidden fields for security
- [x] Has relationship to User
- [ ] Missing interface implementation
- [ ] Missing scopes (e.g., scopeNotExpired)
- [ ] Should extend UserFrosting\Sprinkle\Core\Database\Models\Model

### Migration
- [x] Extends UserFrosting\Sprinkle\Core\Database\Migration
- [x] Has proper up/down methods
- [x] Has foreign key constraint
- [x] Has unique constraint
- [x] Has indexes

### Repository
- [x] Has proper CRUD methods
- [x] Uses dependency injection
- [ ] Could follow UserFrosting repository patterns more closely

### Authenticator
- [x] Handles OAuth flow properly
- [x] Creates users from OAuth data
- [x] Links providers to existing users
- [ ] Could use more type declarations

## Conclusion

**Recommendation: Keep separate oauth_connections table and enhance it to follow UserFrosting 6 standards.**

This approach:
1. Maintains separation of concerns
2. Follows UserFrosting 6 architectural patterns
3. Doesn't break existing functionality
4. Provides clean, maintainable code
5. Supports multiple OAuth providers per user naturally
6. Aligns with how other UserFrosting sprinkles work

The main improvements needed are:
1. Add OAuthConnectionInterface
2. Extend proper base Model class
3. Add proper scopes and methods
4. Enhance documentation
5. Follow all UF6 coding standards

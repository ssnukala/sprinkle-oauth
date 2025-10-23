# OAuth Authentication Flow

## Overview

This document describes the OAuth 2.0 authentication flow implemented in the OAuth Sprinkle.

## Flow Diagram

```
┌─────────────┐                                  ┌──────────────────┐
│             │                                  │                  │
│   Browser   │                                  │  OAuth Provider  │
│   (User)    │                                  │  (Google, etc.)  │
│             │                                  │                  │
└──────┬──────┘                                  └────────┬─────────┘
       │                                                  │
       │  1. Click "Sign in with Google"                 │
       │     GET /oauth/login                            │
       │                                                  │
       │  ┌─────────────────────────────────────────┐   │
       │  │  Display login page with OAuth buttons  │   │
       │  └─────────────────────────────────────────┘   │
       │                                                  │
       │  2. Click OAuth provider button                 │
       │     GET /oauth/google                           │
       │                                                  │
       ▼                                                  │
┌─────────────────────────────┐                         │
│   OAuthController           │                         │
│   redirect()                │                         │
│                             │                         │
│  - Generate state (CSRF)    │                         │
│  - Store in session         │                         │
│  - Get authorization URL    │                         │
└──────────┬──────────────────┘                         │
           │                                             │
           │  3. HTTP 302 Redirect                       │
           │     to authorization URL                    │
           │─────────────────────────────────────────────▶
           │                                             │
           │                                    ┌────────▼──────────┐
           │                                    │  User authorizes  │
           │                                    │  application      │
           │                                    └────────┬──────────┘
           │                                             │
           │  4. HTTP 302 Redirect with code & state    │
           │     GET /oauth/google/callback?            │
           │         code=xxx&state=yyy                 │
           │◀─────────────────────────────────────────────
           │
           ▼
┌─────────────────────────────┐
│   OAuthController           │
│   callback()                │
│                             │
│  - Verify state (CSRF)      │
│  - Exchange code for token  │──────────────┐
└──────────┬──────────────────┘              │
           │                                  │  5. Exchange authorization
           │                                  │     code for access token
           │                                  │
           │                                  ▼
           │                       ┌──────────────────────┐
           │                       │  OAuth Provider API  │
           │                       │  Token endpoint      │
           │                       └──────────┬───────────┘
           │                                  │
           │                                  │  6. Return access token
           │◀─────────────────────────────────┘     & user info
           │
           ▼
┌─────────────────────────────────────────────────────┐
│   OAuthAuthenticationService                        │
│   findOrCreateUser()                                │
│                                                     │
│  Check if OAuth connection exists                   │
│    ├─ YES: Update tokens, return existing user     │
│    └─ NO:  Check if email exists in users table    │
│              ├─ YES: Link OAuth to existing user   │
│              └─ NO:  Create new user account       │
└──────────┬──────────────────────────────────────────┘
           │
           │  7. Save/update OAuth connection
           │
           ▼
┌────────────────────────┐
│   Database             │
│   oauth_connections    │
│                        │
│  - user_id            │
│  - provider           │
│  - provider_user_id   │
│  - access_token       │
│  - refresh_token      │
│  - user_data          │
└──────────┬─────────────┘
           │
           │  8. Set session & redirect
           │
           ▼
┌─────────────────────────────┐
│   Browser                   │
│                             │
│  - User logged in           │
│  - Redirect to dashboard    │
│  - Show success message     │
└─────────────────────────────┘
```

## Detailed Steps

### Step 1: User Initiates OAuth Login

**User Action**: Navigates to `/oauth/login` and clicks an OAuth provider button (e.g., "Sign in with Google")

**What Happens**:
- Browser requests: `GET /oauth/login`
- Server renders login page with enabled OAuth providers
- User clicks provider button

### Step 2: Redirect to OAuth Provider

**Request**: `GET /oauth/google`

**Controller**: `OAuthController::redirect()`

**Process**:
1. Get OAuth provider instance from `OAuthService`
2. Generate authorization URL with proper scopes
3. Generate state parameter for CSRF protection
4. Store state in PHP session: `$_SESSION['oauth_state']['google']`
5. Redirect user to OAuth provider's authorization page

**Example Authorization URL**:
```
https://accounts.google.com/o/oauth2/v2/auth?
  client_id=xxx&
  redirect_uri=https://yourdomain.com/oauth/google/callback&
  response_type=code&
  scope=openid+email+profile&
  state=random-csrf-token
```

### Step 3: User Authorizes on Provider

**Location**: OAuth Provider's website

**User Action**:
- Views permission request
- Logs in to provider account (if not already)
- Approves or denies access

**Provider Response**:
- If approved: Redirects to callback URL with authorization code
- If denied: Redirects with error parameter

### Step 4: Provider Redirects to Callback

**Request**: `GET /oauth/google/callback?code=AUTH_CODE&state=CSRF_TOKEN`

**What Happens**:
- Browser receives redirect from OAuth provider
- Automatically follows redirect to callback URL
- Includes authorization code and state parameter

### Step 5: Exchange Code for Access Token

**Controller**: `OAuthController::callback()`

**Process**:
1. Verify state parameter matches stored value (CSRF protection)
2. Clear stored state from session
3. Extract authorization code from query parameters
4. Call provider's token endpoint to exchange code for access token

**Token Exchange Request**:
```php
POST https://oauth2.googleapis.com/token
Content-Type: application/x-www-form-urlencoded

code=AUTH_CODE&
client_id=xxx&
client_secret=xxx&
redirect_uri=https://yourdomain.com/oauth/google/callback&
grant_type=authorization_code
```

**Token Response**:
```json
{
  "access_token": "ya29.xxx",
  "expires_in": 3599,
  "refresh_token": "1//xxx",
  "scope": "openid email profile",
  "token_type": "Bearer",
  "id_token": "eyJxxx"
}
```

### Step 6: Fetch User Information

**Process**:
1. Use access token to request user information from provider
2. Call provider's user info endpoint
3. Extract user details (email, name, ID, etc.)

**Example User Info Request**:
```
GET https://www.googleapis.com/oauth2/v2/userinfo
Authorization: Bearer ACCESS_TOKEN
```

**User Info Response**:
```json
{
  "id": "123456789",
  "email": "user@example.com",
  "verified_email": true,
  "name": "John Doe",
  "given_name": "John",
  "family_name": "Doe",
  "picture": "https://..."
}
```

### Step 7: Find or Create User

**Service**: `OAuthAuthenticationService::findOrCreateUser()`

**Process Flow**:

```
Check: Does OAuth connection exist?
  provider = 'google'
  provider_user_id = '123456789'

├─ YES: OAuth connection found
│   ├─ Update access token
│   ├─ Update refresh token
│   ├─ Update expiration time
│   └─ Return existing user
│
└─ NO: OAuth connection not found
    │
    Check: Does user with this email exist?
      email = 'user@example.com'
    │
    ├─ YES: User exists
    │   ├─ Create new OAuth connection
    │   ├─ Link to existing user
    │   └─ Return existing user
    │
    └─ NO: User doesn't exist
        ├─ Generate unique username from email
        ├─ Create new user account
        │   - email: from OAuth
        │   - first_name: from OAuth
        │   - last_name: from OAuth
        │   - username: generated
        │   - password: random (user won't need it)
        │   - flag_verified: 1 (OAuth = verified)
        │   - flag_enabled: 1
        ├─ Create OAuth connection
        ├─ Link to new user
        └─ Return new user
```

### Step 8: Save OAuth Connection

**Table**: `oauth_connections`

**Data Saved**:
```php
[
    'user_id' => 42,
    'provider' => 'google',
    'provider_user_id' => '123456789',
    'access_token' => 'ya29.xxx',
    'refresh_token' => '1//xxx',
    'expires_at' => '2024-10-02 12:00:00',
    'user_data' => json_encode([
        'id' => '123456789',
        'email' => 'user@example.com',
        'name' => 'John Doe',
        // ... additional data
    ])
]
```

### Step 9: Set Session and Redirect

**Process**:
1. Set user session: `$_SESSION['user_id'] = $user->id`
2. Set success message
3. Determine redirect URL (dashboard or stored intended page)
4. Redirect user

**Example**:
```php
$_SESSION['user_id'] = $user->id;
$_SESSION['alerts']['success'][] = 'Successfully logged in with Google.';
$redirectUrl = $_SESSION['redirect_after_login'] ?? '/dashboard';
// HTTP 302 redirect to $redirectUrl
```

## Security Measures

### CSRF Protection (State Parameter)

```php
// Before redirect
$state = bin2hex(random_bytes(32));
$_SESSION['oauth_state']['google'] = $state;

// On callback
$receivedState = $_GET['state'];
$storedState = $_SESSION['oauth_state']['google'];
if ($receivedState !== $storedState) {
    // REJECT: Potential CSRF attack
}
unset($_SESSION['oauth_state']['google']);
```

### Token Storage

- Access tokens stored in database (consider encryption)
- Tokens hidden from API responses
- Refresh tokens stored securely
- Tokens can be revoked by deleting connection

### Password Generation

- Random 32-byte password for OAuth users
- User cannot log in with password without reset
- Prevents unauthorized password-based access

## Multiple Provider Support

### Linking Additional Providers

**Flow**:
1. User already logged in
2. Goes to settings page
3. Clicks "Connect LinkedIn"
4. Same OAuth flow as above
5. Instead of creating/finding user, link to current user

**Code**:
```php
// In callback, check for link mode
if (isset($_SESSION['oauth_link_mode'])) {
    $userId = $_SESSION['user_id'];
    $connection = $authService->linkProvider($userId, $provider, $providerUserData);
    unset($_SESSION['oauth_link_mode']);
}
```

### Database Structure

One user can have multiple connections:

```
users
  id: 42
  email: john@example.com

oauth_connections
  id: 1  | user_id: 42 | provider: google   | provider_user_id: 123
  id: 2  | user_id: 42 | provider: linkedin | provider_user_id: 456
  id: 3  | user_id: 42 | provider: facebook | provider_user_id: 789
```

## Error Handling

### Common Error Scenarios

1. **User Denies Authorization**
   - Provider redirects with `error` parameter
   - Show error message, redirect to login

2. **State Mismatch**
   - Potential CSRF attack
   - Reject request, show error

3. **Invalid Code**
   - Authorization code expired or invalid
   - Token exchange fails
   - Show error, ask to retry

4. **No Email from Provider**
   - Provider doesn't return email
   - Cannot create user without email
   - Show error message

5. **Database Error**
   - User/connection creation fails
   - Rollback transaction
   - Show generic error

## Provider-Specific Notes

### Google
- Requires Google+ API enabled
- Returns `sub` field for user ID
- Very reliable email provision

### Facebook
- Requires app review for production
- Email permission can be denied by user
- Returns `id` field for user ID

### LinkedIn
- Requires "Sign In with LinkedIn" product
- More restrictive API access
- Email always provided

### Microsoft
- Works with personal and work accounts
- Tenant configuration affects behavior
- Returns `id` or `sub` for user ID

## Performance Considerations

- OAuth flow involves multiple HTTP requests
- Consider caching provider configurations
- Token expiration checking for API calls
- Database queries optimized with indexes

## Testing

Recommended test scenarios:

1. ✅ New user signup via OAuth
2. ✅ Existing user login via OAuth
3. ✅ Link additional provider to account
4. ✅ Login with different linked providers
5. ✅ Error handling (denied access, invalid state)
6. ✅ Multiple users with same provider
7. ✅ Provider account email change handling

## Troubleshooting Flow

```
Issue: OAuth not working

├─ Check: Are OAuth buttons visible?
│   NO → Check: Environment variables set?
│       NO → Set in .env file
│       YES → Check: Cache cleared?
│           NO → Run: php bakery clear-cache
│
├─ Check: Does redirect work?
│   NO → Check: Provider credentials valid?
│       NO → Regenerate in provider console
│       YES → Check: Redirect URI matches?
│           NO → Update in provider console
│
├─ Check: Does callback fail?
│   YES → Check: State mismatch error?
│       YES → Check: Sessions working?
│           NO → Fix session configuration
│       NO → Check: Token exchange error?
│           YES → Check: Credentials correct?
│               NO → Update credentials
│
└─ Check: Does user creation fail?
    YES → Check: Database migration run?
        NO → Run: php bakery migrate
        YES → Check: Database logs
            → Review error details
```

## References

- [OAuth 2.0 RFC 6749](https://tools.ietf.org/html/rfc6749)
- [UserFrosting Documentation](https://learn.userfrosting.com/)
- [Google OAuth Guide](https://developers.google.com/identity/protocols/oauth2)
- [Facebook Login Guide](https://developers.facebook.com/docs/facebook-login/)
- [LinkedIn OAuth Guide](https://docs.microsoft.com/en-us/linkedin/shared/authentication/authentication)
- [Microsoft OAuth Guide](https://docs.microsoft.com/en-us/azure/active-directory/develop/v2-oauth2-auth-code-flow)

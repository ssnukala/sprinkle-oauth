# Examples - OAuth Sprinkle Usage

This document provides practical examples of using the OAuth Sprinkle in various scenarios.

## Table of Contents
1. [Basic Setup](#basic-setup)
2. [Custom Login Page](#custom-login-page)
3. [User Settings Integration](#user-settings-integration)
4. [Accessing OAuth Data](#accessing-oauth-data)
5. [Custom User Creation](#custom-user-creation)
6. [API Integration](#api-integration)
7. [Testing Scenarios](#testing-scenarios)

---

## Basic Setup

### Example 1: Simple Installation

After installing the sprinkle, configure Google OAuth:

```env
# .env
OAUTH_GOOGLE_CLIENT_ID=123456789.apps.googleusercontent.com
OAUTH_GOOGLE_CLIENT_SECRET=abcdef123456
```

Test the setup:
```bash
php bakery clear-cache
# Visit: http://localhost/oauth/login
```

---

## Custom Login Page

### Example 2: Replace Default Login with OAuth Login

**Route Configuration** (`app/src/Routes/CustomRoutes.php`):
```php
<?php

declare(strict_types=1);

namespace UserFrosting\Sprinkle\YourSprinkle\Routes;

use Slim\App;
use UserFrosting\Routes\RouteDefinitionInterface;
use UserFrosting\Sprinkle\OAuth\Controller\OAuthController;

class CustomRoutes implements RouteDefinitionInterface
{
    public function register(App $app): void
    {
        // Override default login route
        $app->get('/login', [OAuthController::class, 'loginPage'])
            ->setName('login');
    }
}
```

### Example 3: Add OAuth Buttons to Existing Login Page

**Template** (`app/templates/pages/login.html.twig`):
```twig
{# Your existing login form #}
<form action="{{ urlFor('login') }}" method="post">
    {# ... username/password fields ... #}
</form>

{# Add OAuth buttons #}
{% set oauthService = container.get('UserFrosting\\Sprinkle\\OAuth\\Service\\OAuthService') %}
{% set enabledProviders = oauthService.getEnabledProviders() %}

{% if enabledProviders is not empty %}
<div class="oauth-section">
    <p class="text-center">Or sign in with:</p>
    <div class="oauth-buttons">
        {% for provider in enabledProviders %}
        <a href="{{ urlFor('oauth.redirect', {'provider': provider}) }}" 
           class="btn btn-{{ provider }}-oauth">
            <i class="fab fa-{{ provider }}"></i> 
            {{ provider|capitalize }}
        </a>
        {% endfor %}
    </div>
</div>
{% endif %}
```

### Example 4: Custom Styled OAuth Buttons

**CSS** (`app/assets/css/oauth-buttons.css`):
```css
.oauth-buttons {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin: 20px 0;
}

.btn-google-oauth {
    background-color: #DB4437;
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 4px;
    font-size: 16px;
    cursor: pointer;
    transition: background-color 0.3s;
}

.btn-google-oauth:hover {
    background-color: #C33D2E;
    color: white;
}

.btn-facebook-oauth {
    background-color: #4267B2;
    color: white;
}

.btn-linkedin-oauth {
    background-color: #0077B5;
    color: white;
}

.btn-microsoft-oauth {
    background-color: #00A4EF;
    color: white;
}
```

---

## User Settings Integration

### Example 5: Display OAuth Connections in User Profile

**Controller** (`app/src/Controller/ProfileController.php`):
```php
<?php
namespace UserFrosting\Sprinkle\MyApp\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use UserFrosting\Sprinkle\OAuth\Repository\OAuthConnectionRepository;
use Slim\Views\Twig;

class ProfileController
{
    protected OAuthConnectionRepository $oauthRepo;
    protected Twig $view;

    public function __construct(
        OAuthConnectionRepository $oauthRepo,
        Twig $view
    ) {
        $this->oauthRepo = $oauthRepo;
        $this->view = $view;
    }

    public function settings(Request $request, Response $response): Response
    {
        $userId = $_SESSION['user_id'];
        
        // Get user's OAuth connections
        $connections = $this->oauthRepo->findByUserId($userId);
        
        // Format for template
        $userConnections = [];
        foreach ($connections as $connection) {
            $userConnections[$connection->provider] = [
                'connected_at' => $connection->created_at->format('Y-m-d H:i:s'),
                'provider_email' => $connection->user_data['email'] ?? 'N/A',
            ];
        }
        
        return $this->view->render($response, 'pages/profile/settings.html.twig', [
            'userConnections' => $userConnections,
        ]);
    }
}
```

**Template** (`app/templates/pages/profile/settings.html.twig`):
```twig
<div class="card">
    <div class="card-header">
        <h3>Account Settings</h3>
    </div>
    <div class="card-body">
        {# Include OAuth connections component #}
        {% include 'components/oauth-connections.html.twig' with {
            'userConnections': userConnections
        } %}
    </div>
</div>
```

### Example 6: Show OAuth Connection Count in Dashboard

**Dashboard Widget**:
```twig
{% set userId = current_user.id %}
{% set oauthRepo = container.get('UserFrosting\\Sprinkle\\OAuth\\Repository\\OAuthConnectionRepository') %}
{% set connections = oauthRepo.findByUserId(userId) %}

<div class="info-box">
    <span class="info-box-icon bg-info">
        <i class="fas fa-link"></i>
    </span>
    <div class="info-box-content">
        <span class="info-box-text">OAuth Connections</span>
        <span class="info-box-number">{{ connections|length }}</span>
    </div>
</div>
```

---

## Accessing OAuth Data

### Example 7: Get User's Google Calendar Events

```php
<?php
namespace UserFrosting\Sprinkle\MyApp\Service;

use UserFrosting\Sprinkle\OAuth\Repository\OAuthConnectionRepository;
use UserFrosting\Sprinkle\OAuth\Service\OAuthService;

class CalendarService
{
    protected OAuthConnectionRepository $connectionRepo;
    protected OAuthService $oauthService;

    public function __construct(
        OAuthConnectionRepository $connectionRepo,
        OAuthService $oauthService
    ) {
        $this->connectionRepo = $connectionRepo;
        $this->oauthService = $oauthService;
    }

    public function getGoogleCalendarEvents(int $userId): array
    {
        // Get user's Google OAuth connection
        $connection = $this->connectionRepo->findByUserIdAndProvider($userId, 'google');
        
        if (!$connection) {
            throw new \Exception('Google account not connected');
        }
        
        // Check if token is expired
        if ($connection->expires_at < now()) {
            // TODO: Implement token refresh
            throw new \Exception('Access token expired');
        }
        
        // Use access token to fetch calendar events
        $accessToken = $connection->access_token;
        
        $ch = curl_init('https://www.googleapis.com/calendar/v3/calendars/primary/events');
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $accessToken],
            CURLOPT_RETURNTRANSFER => true,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new \Exception('Failed to fetch calendar events');
        }
        
        return json_decode($response, true);
    }
}
```

### Example 8: Get User's Profile Picture from OAuth

```php
<?php
public function getUserProfilePicture(int $userId): ?string
{
    $connections = $this->connectionRepo->findByUserId($userId);
    
    foreach ($connections as $connection) {
        $userData = $connection->user_data;
        
        // Try to get profile picture from any connected provider
        if (isset($userData['picture'])) {
            return $userData['picture']; // Google, Microsoft
        }
        if (isset($userData['avatar_url'])) {
            return $userData['avatar_url']; // LinkedIn
        }
        if (isset($userData['id']) && $connection->provider === 'facebook') {
            return "https://graph.facebook.com/{$userData['id']}/picture?type=large";
        }
    }
    
    return null; // No profile picture found
}
```

---

## Custom User Creation

### Example 9: Add Custom Fields During OAuth Signup

**Extend OAuthAuthenticationService**:
```php
<?php
namespace UserFrosting\Sprinkle\MyApp\Service;

use UserFrosting\Sprinkle\OAuth\Service\OAuthAuthenticationService as BaseService;

class CustomOAuthAuthenticationService extends BaseService
{
    protected function createUserFromOAuth(
        string $provider, 
        array $providerUserData, 
        string $email
    ) {
        $userClass = 'UserFrosting\Sprinkle\Account\Database\Models\User';
        
        $userData = [
            'email' => $email,
            'first_name' => $this->extractFirstName($provider, $providerUserData),
            'last_name' => $this->extractLastName($provider, $providerUserData),
            'user_name' => $this->generateUsername($email),
            'flag_verified' => 1,
            'flag_enabled' => 1,
            'password' => bin2hex(random_bytes(32)),
            
            // Custom fields
            'signup_source' => 'oauth_' . $provider,
            'profile_picture' => $providerUserData['picture'] ?? null,
            'locale' => $providerUserData['locale'] ?? 'en_US',
        ];
        
        return $userClass::create($userData);
    }
}
```

**Register Custom Service**:
```php
<?php
// app/src/ServicesProvider/CustomOAuthServicesProvider.php
namespace UserFrosting\Sprinkle\MyApp\ServicesProvider;

use Psr\Container\ContainerInterface;
use UserFrosting\Sprinkle\OAuth\Service\OAuthAuthenticationService;
use UserFrosting\Sprinkle\MyApp\Service\CustomOAuthAuthenticationService;
use UserFrosting\Sprinkle\OAuth\Repository\OAuthConnectionRepository;
use UserFrosting\ServicesProvider\ServicesProviderInterface;

class CustomOAuthServicesProvider implements ServicesProviderInterface
{
    public function register(): array
    {
        return [
            // Override default OAuth authentication service
            OAuthAuthenticationService::class => \DI\factory(function (ContainerInterface $c) {
                return new CustomOAuthAuthenticationService(
                    $c->get(OAuthConnectionRepository::class)
                );
            }),
        ];
    }
}
```

### Example 10: Send Welcome Email After OAuth Signup

```php
<?php
protected function createUserFromOAuth(
    string $provider, 
    array $providerUserData, 
    string $email
) {
    // Create user as usual
    $user = parent::createUserFromOAuth($provider, $providerUserData, $email);
    
    // Send welcome email
    $this->sendWelcomeEmail($user, $provider);
    
    return $user;
}

protected function sendWelcomeEmail($user, string $provider): void
{
    $mailer = $this->container->get('mailer');
    
    $message = new \UserFrosting\Sprinkle\Core\Mail\EmailMessage();
    $message->to($user->email, $user->first_name . ' ' . $user->last_name)
            ->subject('Welcome to ' . config('site.title'))
            ->body("Welcome! You signed up using " . ucfirst($provider) . ".");
    
    $mailer->send($message);
}
```

---

## API Integration

### Example 11: RESTful API Endpoint for OAuth Connections

**Controller**:
```php
<?php
namespace UserFrosting\Sprinkle\MyApp\Controller\Api;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use UserFrosting\Sprinkle\OAuth\Repository\OAuthConnectionRepository;

class OAuthApiController
{
    protected OAuthConnectionRepository $repo;

    public function __construct(OAuthConnectionRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * GET /api/oauth/connections
     */
    public function listConnections(Request $request, Response $response): Response
    {
        $userId = $_SESSION['user_id'];
        $connections = $this->repo->findByUserId($userId);
        
        $data = $connections->map(function ($connection) {
            return [
                'provider' => $connection->provider,
                'connected_at' => $connection->created_at->toIso8601String(),
                'email' => $connection->user_data['email'] ?? null,
            ];
        });
        
        $response->getBody()->write(json_encode([
            'connections' => $data,
        ]));
        
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * DELETE /api/oauth/connections/{provider}
     */
    public function deleteConnection(Request $request, Response $response, array $args): Response
    {
        $userId = $_SESSION['user_id'];
        $provider = $args['provider'];
        
        $connection = $this->repo->findByUserIdAndProvider($userId, $provider);
        
        if (!$connection) {
            $response->getBody()->write(json_encode([
                'error' => 'Connection not found'
            ]));
            return $response->withStatus(404)
                           ->withHeader('Content-Type', 'application/json');
        }
        
        $this->repo->delete($connection);
        
        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'Connection deleted'
        ]));
        
        return $response->withHeader('Content-Type', 'application/json');
    }
}
```

**Routes** (`app/src/Routes/OAuthApiRoutes.php`):
```php
<?php

declare(strict_types=1);

namespace UserFrosting\Sprinkle\YourSprinkle\Routes;

use Slim\App;
use UserFrosting\Routes\RouteDefinitionInterface;

class OAuthApiRoutes implements RouteDefinitionInterface
{
    public function register(App $app): void
    {
        $app->get('/api/oauth/connections', 
            [OAuthApiController::class, 'listConnections']);
        
        $app->delete('/api/oauth/connections/{provider}', 
            [OAuthApiController::class, 'deleteConnection']);
    }
}
```

---

## Testing Scenarios

### Example 12: Manual Test Checklist

```bash
# Test 1: New user signup with Google
# 1. Clear database or use fresh email
# 2. Visit /oauth/login
# 3. Click "Sign in with Google"
# 4. Authorize with Google account
# 5. Verify: User created, logged in, redirected to dashboard

# Test 2: Existing user login with Google
# 1. Use same Google account as Test 1
# 2. Visit /oauth/login
# 3. Click "Sign in with Google"
# 4. Verify: Logged in immediately, no duplicate user created

# Test 3: Link multiple providers
# 1. Log in with Google (from Test 1)
# 2. Go to settings page
# 3. Click "Connect LinkedIn"
# 4. Authorize with LinkedIn
# 5. Verify: Both connections shown in settings

# Test 4: Login with linked provider
# 1. Log out
# 2. Visit /oauth/login
# 3. Click "Sign in with LinkedIn"
# 4. Verify: Logged in as same user from Test 1

# Test 5: Error handling - denied access
# 1. Visit /oauth/login
# 2. Click "Sign in with Facebook"
# 3. Click "Cancel" on Facebook authorization
# 4. Verify: Redirected to login with error message

# Test 6: Multiple users with same provider
# 1. Log out
# 2. Use different Google account
# 3. Sign in with Google
# 4. Verify: New separate user created
```

### Example 13: Database Verification

```sql
-- Check oauth_connections table
SELECT 
    oc.id,
    oc.provider,
    u.email,
    u.user_name,
    oc.created_at
FROM oauth_connections oc
JOIN users u ON u.id = oc.user_id
ORDER BY oc.created_at DESC;

-- Find users with multiple OAuth connections
SELECT 
    u.id,
    u.email,
    COUNT(oc.id) as connection_count,
    GROUP_CONCAT(oc.provider) as providers
FROM users u
JOIN oauth_connections oc ON oc.user_id = u.id
GROUP BY u.id
HAVING connection_count > 1;

-- Find OAuth users (users created via OAuth)
SELECT u.*
FROM users u
JOIN oauth_connections oc ON oc.user_id = u.id
LEFT JOIN user_password_resets pr ON pr.user_id = u.id
WHERE pr.id IS NULL  -- Never reset password
GROUP BY u.id;
```

---

## Advanced Examples

### Example 14: Auto-Link OAuth Account by Email

**Scenario**: User exists with email, registers with OAuth using same email

```php
<?php
public function findOrCreateUser(string $provider, array $providerUserData): array
{
    $providerId = $this->extractProviderId($provider, $providerUserData);
    $email = $this->extractEmail($provider, $providerUserData);
    
    // Check if OAuth connection exists
    $connection = $this->connectionRepository
        ->findByProviderAndProviderUserId($provider, $providerId);
    
    if ($connection) {
        $this->updateConnection($connection, $providerUserData);
        return [
            'user' => $connection->user,
            'connection' => $connection,
            'isNewUser' => false,
            'wasLinked' => false,
        ];
    }
    
    // Check if user with email exists
    $userClass = 'UserFrosting\Sprinkle\Account\Database\Models\User';
    $user = $userClass::where('email', $email)->first();
    
    if ($user) {
        // Auto-link to existing user
        $connection = $this->createConnection(
            $user->id, 
            $provider, 
            $providerId, 
            $providerUserData
        );
        
        // Send notification email
        $this->notifyUserAboutNewConnection($user, $provider);
        
        return [
            'user' => $user,
            'connection' => $connection,
            'isNewUser' => false,
            'wasLinked' => true, // New flag
        ];
    }
    
    // Create new user
    $user = $this->createUserFromOAuth($provider, $providerUserData, $email);
    $connection = $this->createConnection(
        $user->id, 
        $provider, 
        $providerId, 
        $providerUserData
    );
    
    return [
        'user' => $user,
        'connection' => $connection,
        'isNewUser' => true,
        'wasLinked' => false,
    ];
}
```

---

These examples should help you integrate and customize the OAuth Sprinkle for your specific needs!

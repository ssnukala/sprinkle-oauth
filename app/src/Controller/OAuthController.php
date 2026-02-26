<?php

declare(strict_types=1);

/*
 * UserFrosting OAuth Sprinkle (https://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-oauth
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-oauth/blob/master/LICENSE (MIT License)
 */

namespace UserFrosting\Sprinkle\OAuth\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use UserFrosting\Alert\AlertStream;
use UserFrosting\Sprinkle\Account\Authenticate\Authenticator;
use UserFrosting\Sprinkle\OAuth\Factory\OAuthProviderFactory;
use UserFrosting\Sprinkle\OAuth\Authenticator\OAuthAuthenticator;
use UserFrosting\Sprinkle\OAuth\Repository\OAuthConnectionRepository;
use Slim\Views\Twig;

/**
 * OAuth Controller
 *
 * Handles OAuth authentication flows using UF6 Authenticator for session management.
 */
class OAuthController
{
    public function __construct(
        protected OAuthProviderFactory $oauthFactory,
        protected OAuthAuthenticator $oauthAuthenticator,
        protected Authenticator $authenticator,
        protected OAuthConnectionRepository $connectionRepository,
        protected AlertStream $alertStream,
        protected Twig $view
    ) {
    }

    /**
     * Redirect to OAuth provider for authentication.
     * If ?popup=1 is present, stores popup flag in session for the callback.
     * Generates PKCE code_challenge for defense-in-depth (OAuth 2.1).
     */
    public function redirect(Request $request, Response $response, array $args): Response
    {
        $provider = $args['provider'] ?? '';
        $queryParams = $request->getQueryParams();

        try {
            // Generate PKCE pair — code_verifier stored in session, code_challenge sent to provider
            $pkce = $this->oauthFactory->generatePkce();
            $_SESSION['oauth_pkce'][$provider] = $pkce['code_verifier'];

            $authUrl = $this->oauthFactory->getAuthorizationUrl($provider, $pkce);
            $state = $this->oauthFactory->getState($provider);

            // Store state in session for CSRF protection
            $_SESSION['oauth_state'][$provider] = $state;

            // Store popup flag if present (frontend popup mode)
            if (!empty($queryParams['popup'])) {
                $_SESSION['oauth_popup'][$provider] = true;
            }

            return $response
                ->withHeader('Location', $authUrl)
                ->withStatus(302);
        } catch (\Exception $e) {
            // If popup mode, render error as postMessage page
            if (!empty($queryParams['popup'])) {
                return $this->renderPopupResult($response, $provider, false, $e->getMessage());
            }

            $this->alertStream->addMessage('danger', 'OAuth provider not available: ' . $e->getMessage());
            return $response
                ->withHeader('Location', '/login')
                ->withStatus(302);
        }
    }

    /**
     * Handle OAuth callback.
     * Supports both redirect mode (traditional) and popup mode (postMessage to opener).
     */
    public function callback(Request $request, Response $response, array $args): Response
    {
        $provider = $args['provider'] ?? '';
        $queryParams = $request->getQueryParams();

        // Check if this callback is from a popup flow
        $isPopup = !empty($_SESSION['oauth_popup'][$provider]);
        unset($_SESSION['oauth_popup'][$provider]);

        // Verify state for CSRF protection (skip for Facebook as it uses its own)
        if ($provider !== 'facebook') {
            $state = $queryParams['state'] ?? '';
            $storedState = $_SESSION['oauth_state'][$provider] ?? '';

            if (empty($state) || $state !== $storedState) {
                if ($isPopup) {
                    return $this->renderPopupResult($response, $provider, false, 'Invalid OAuth state. Please try again.');
                }
                $this->alertStream->addMessage('danger', 'Invalid OAuth state. Please try again.');
                return $response
                    ->withHeader('Location', '/login')
                    ->withStatus(302);
            }
        }

        // Clear stored state
        unset($_SESSION['oauth_state'][$provider]);

        // Check for errors from provider
        if (isset($queryParams['error'])) {
            $message = 'OAuth authentication failed: ' . $queryParams['error'];
            if ($isPopup) {
                return $this->renderPopupResult($response, $provider, false, $message);
            }
            $this->alertStream->addMessage('danger', $message);
            return $response
                ->withHeader('Location', '/login')
                ->withStatus(302);
        }

        // Exchange code for access token
        $code = $queryParams['code'] ?? '';
        if (empty($code)) {
            $message = 'No authorization code received.';
            if ($isPopup) {
                return $this->renderPopupResult($response, $provider, false, $message);
            }
            $this->alertStream->addMessage('danger', $message);
            return $response
                ->withHeader('Location', '/login')
                ->withStatus(302);
        }

        try {
            // Check if this is a link-mode callback (linking provider to existing account)
            $isLinkMode = !empty($_SESSION['oauth_link_mode']);
            unset($_SESSION['oauth_link_mode']);

            // Retrieve PKCE code_verifier from session (generated during redirect)
            $codeVerifier = $_SESSION['oauth_pkce'][$provider] ?? null;
            unset($_SESSION['oauth_pkce'][$provider]);

            // Get access token (with PKCE code_verifier for defense-in-depth)
            $tokenData = $this->oauthFactory->getAccessToken($provider, $code, $codeVerifier);

            // Get user details from provider
            $providerUserData = $this->oauthFactory->getUserInfo($provider, $tokenData['access_token']);
            $providerUserData['token'] = $tokenData;

            if ($isLinkMode) {
                // Link provider to currently authenticated user
                $currentUser = $this->authenticator->user();
                if ($currentUser === null) {
                    $message = 'You must be logged in to link an OAuth provider.';
                    if ($isPopup) {
                        return $this->renderPopupResult($response, $provider, false, $message, 'link');
                    }
                    $this->alertStream->addMessage('danger', $message);
                    return $response
                        ->withHeader('Location', '/login')
                        ->withStatus(302);
                }

                $this->oauthAuthenticator->linkProvider($currentUser->id, $provider, $providerUserData);

                if ($isPopup) {
                    return $this->renderPopupResult($response, $provider, true, ucfirst($provider) . ' account linked successfully.', 'link');
                }

                $this->alertStream->addMessage('success', ucfirst($provider) . ' account linked successfully.');
                return $response
                    ->withHeader('Location', '/settings')
                    ->withStatus(302);
            }

            // Standard login flow: find or create user
            $result = $this->oauthAuthenticator->findOrCreateUser($provider, $providerUserData);
            $user = $result['user'];
            $isNewUser = $result['isNewUser'];

            // Log in user via UF6 Authenticator (handles session, events, CSRF)
            $this->authenticator->login($user);

            $message = $isNewUser
                ? 'Welcome! Your account has been created.'
                : 'Successfully logged in with ' . ucfirst($provider) . '.';

            $redirectUrl = $_SESSION['redirect_after_login'] ?? '/dashboard';
            unset($_SESSION['redirect_after_login']);

            if ($isPopup) {
                return $this->renderPopupResult($response, $provider, true, $message, 'login', [
                    'isNewUser' => $isNewUser,
                    'redirect' => $redirectUrl,
                    'user' => [
                        'user_name' => $user->user_name,
                        'email' => $user->email,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                    ],
                ]);
            }

            $this->alertStream->addMessage('success', $message);

            return $response
                ->withHeader('Location', $redirectUrl)
                ->withStatus(302);
        } catch (\Exception $e) {
            $message = 'OAuth authentication error: ' . $e->getMessage();
            if ($isPopup) {
                return $this->renderPopupResult($response, $provider, false, $message);
            }
            $this->alertStream->addMessage('danger', $message);
            return $response
                ->withHeader('Location', '/login')
                ->withStatus(302);
        }
    }

    /**
     * Display login page with OAuth options.
     */
    public function loginPage(Request $request, Response $response): Response
    {
        $enabledProviders = $this->oauthFactory->getEnabledProviders();

        return $this->view->render($response, 'pages/oauth-login.html.twig', [
            'enabledProviders' => $enabledProviders,
        ]);
    }

    /**
     * Link OAuth provider to current user's account.
     * Redirects to provider's auth URL with link_mode flag set.
     * Supports popup mode when ?popup=1 is present.
     */
    public function linkProvider(Request $request, Response $response, array $args): Response
    {
        $provider = $args['provider'] ?? '';
        $queryParams = $request->getQueryParams();
        $isPopup = !empty($queryParams['popup']);

        $currentUser = $this->authenticator->user();
        if ($currentUser === null) {
            if ($isPopup) {
                return $this->renderPopupResult($response, $provider, false, 'You must be logged in to link an OAuth provider.', 'link');
            }
            $this->alertStream->addMessage('danger', 'You must be logged in to link an OAuth provider.');
            return $response
                ->withHeader('Location', '/login')
                ->withStatus(302);
        }

        try {
            // Generate PKCE pair for defense-in-depth
            $pkce = $this->oauthFactory->generatePkce();
            $_SESSION['oauth_pkce'][$provider] = $pkce['code_verifier'];

            $authUrl = $this->oauthFactory->getAuthorizationUrl($provider, $pkce);
            $state = $this->oauthFactory->getState($provider);

            // Store state and link flag in session
            $_SESSION['oauth_state'][$provider] = $state;
            $_SESSION['oauth_link_mode'] = true;

            // Store popup flag so callback knows to render postMessage page
            if ($isPopup) {
                $_SESSION['oauth_popup'][$provider] = true;
            }

            return $response
                ->withHeader('Location', $authUrl)
                ->withStatus(302);
        } catch (\Exception $e) {
            if ($isPopup) {
                return $this->renderPopupResult($response, $provider, false, $e->getMessage(), 'link');
            }
            $this->alertStream->addMessage('danger', 'OAuth provider not available: ' . $e->getMessage());
            return $response
                ->withHeader('Location', '/settings')
                ->withStatus(302);
        }
    }

    /**
     * Disconnect OAuth provider from current user's account.
     */
    public function disconnect(Request $request, Response $response, array $args): Response
    {
        $currentUser = $this->authenticator->user();
        if ($currentUser === null) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'You must be logged in.',
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        $provider = $args['provider'] ?? '';

        try {
            $connection = $this->connectionRepository->findByUserIdAndProvider($currentUser->id, $provider);

            if (!$connection) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'OAuth connection not found.',
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            $this->connectionRepository->delete($connection);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'OAuth provider disconnected successfully.',
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Error disconnecting provider: ' . $e->getMessage(),
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Get current user's OAuth connections.
     * Returns a JSON object keyed by provider name.
     */
    public function connections(Request $request, Response $response): Response
    {
        $currentUser = $this->authenticator->user();
        if ($currentUser === null) {
            $response->getBody()->write(json_encode([
                'connections' => [],
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        try {
            $connections = $this->connectionRepository->findByUserId($currentUser->id);
            $result = [];

            foreach ($connections as $connection) {
                $result[$connection->provider] = [
                    'id' => $connection->id,
                    'provider' => $connection->provider,
                    'provider_user_id' => $connection->provider_user_id,
                    'expires_at' => $connection->expires_at,
                    'created_at' => $connection->created_at,
                    'updated_at' => $connection->updated_at,
                ];
            }

            $response->getBody()->write(json_encode([
                'connections' => $result,
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'connections' => [],
                'error' => $e->getMessage(),
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Render a minimal HTML page that posts the OAuth result back to the opener window
     * via window.postMessage, then closes itself. Used for popup-based OAuth flows.
     *
     * @param Response $response  PSR-7 response
     * @param string   $provider  Provider name
     * @param bool     $success   Whether the OAuth flow succeeded
     * @param string   $message   Message to display/pass back
     * @param string   $action    OAuth action ('login' or 'link')
     * @param array    $extra     Additional data to pass back
     */
    protected function renderPopupResult(
        Response $response,
        string $provider,
        bool $success,
        string $message = '',
        string $action = 'login',
        array $extra = []
    ): Response {
        $data = array_merge([
            'type' => 'oauth_result',
            'provider' => $provider,
            'success' => $success,
            'message' => $message,
            'action' => $action,
        ], $extra);

        $jsonData = json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head><title>OAuth</title></head>
<body>
<p>Completing authentication...</p>
<script>
(function() {
    var data = {$jsonData};
    if (window.opener) {
        window.opener.postMessage(data, window.location.origin);
        window.close();
    } else {
        // Fallback: redirect if no opener (popup was blocked, opened in new tab)
        window.location.href = data.redirect || '/';
    }
})();
</script>
</body>
</html>
HTML;

        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }
}

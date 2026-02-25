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
     */
    public function redirect(Request $request, Response $response, array $args): Response
    {
        $provider = $args['provider'] ?? '';

        try {
            $authUrl = $this->oauthFactory->getAuthorizationUrl($provider);
            $state = $this->oauthFactory->getState($provider);

            // Store state in session for CSRF protection
            $_SESSION['oauth_state'][$provider] = $state;

            return $response
                ->withHeader('Location', $authUrl)
                ->withStatus(302);
        } catch (\Exception $e) {
            $this->alertStream->addMessage('danger', 'OAuth provider not available: ' . $e->getMessage());
            return $response
                ->withHeader('Location', '/login')
                ->withStatus(302);
        }
    }

    /**
     * Handle OAuth callback.
     */
    public function callback(Request $request, Response $response, array $args): Response
    {
        $provider = $args['provider'] ?? '';
        $queryParams = $request->getQueryParams();

        // Verify state for CSRF protection (skip for Facebook as it uses its own)
        if ($provider !== 'facebook') {
            $state = $queryParams['state'] ?? '';
            $storedState = $_SESSION['oauth_state'][$provider] ?? '';

            if (empty($state) || $state !== $storedState) {
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
            $this->alertStream->addMessage('danger', 'OAuth authentication failed: ' . $queryParams['error']);
            return $response
                ->withHeader('Location', '/login')
                ->withStatus(302);
        }

        // Exchange code for access token
        $code = $queryParams['code'] ?? '';
        if (empty($code)) {
            $this->alertStream->addMessage('danger', 'No authorization code received.');
            return $response
                ->withHeader('Location', '/login')
                ->withStatus(302);
        }

        try {
            // Check if this is a link-mode callback (linking provider to existing account)
            $isLinkMode = !empty($_SESSION['oauth_link_mode']);
            unset($_SESSION['oauth_link_mode']);

            // Get access token
            $tokenData = $this->oauthFactory->getAccessToken($provider, $code);

            // Get user details from provider
            $providerUserData = $this->oauthFactory->getUserInfo($provider, $tokenData['access_token']);
            $providerUserData['token'] = $tokenData;

            if ($isLinkMode) {
                // Link provider to currently authenticated user
                $currentUser = $this->authenticator->user();
                if ($currentUser === null) {
                    $this->alertStream->addMessage('danger', 'You must be logged in to link an OAuth provider.');
                    return $response
                        ->withHeader('Location', '/login')
                        ->withStatus(302);
                }

                $this->oauthAuthenticator->linkProvider($currentUser->id, $provider, $providerUserData);
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

            if ($isNewUser) {
                $this->alertStream->addMessage('success', 'Welcome! Your account has been created.');
            } else {
                $this->alertStream->addMessage('success', 'Successfully logged in with ' . ucfirst($provider) . '.');
            }

            // Redirect to dashboard or intended page
            $redirectUrl = $_SESSION['redirect_after_login'] ?? '/dashboard';
            unset($_SESSION['redirect_after_login']);

            return $response
                ->withHeader('Location', $redirectUrl)
                ->withStatus(302);
        } catch (\Exception $e) {
            $this->alertStream->addMessage('danger', 'OAuth authentication error: ' . $e->getMessage());
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
     */
    public function linkProvider(Request $request, Response $response, array $args): Response
    {
        $currentUser = $this->authenticator->user();
        if ($currentUser === null) {
            $this->alertStream->addMessage('danger', 'You must be logged in to link an OAuth provider.');
            return $response
                ->withHeader('Location', '/login')
                ->withStatus(302);
        }

        $provider = $args['provider'] ?? '';

        try {
            $authUrl = $this->oauthFactory->getAuthorizationUrl($provider);
            $state = $this->oauthFactory->getState($provider);

            // Store state and link flag in session
            $_SESSION['oauth_state'][$provider] = $state;
            $_SESSION['oauth_link_mode'] = true;

            return $response
                ->withHeader('Location', $authUrl)
                ->withStatus(302);
        } catch (\Exception $e) {
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
}

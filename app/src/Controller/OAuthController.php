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
use UserFrosting\Sprinkle\OAuth\Service\OAuthService;
use UserFrosting\Sprinkle\OAuth\Service\OAuthAuthenticationService;
use UserFrosting\Sprinkle\Core\Exceptions\NotFoundException;
use Slim\Views\Twig;

/**
 * OAuth Controller
 * 
 * Handles OAuth authentication flows
 */
class OAuthController
{
    /**
     * @var OAuthService
     */
    protected OAuthService $oauthService;

    /**
     * @var OAuthAuthenticationService
     */
    protected OAuthAuthenticationService $authService;

    /**
     * @var Twig
     */
    protected Twig $view;

    /**
     * Constructor
     *
     * @param OAuthService $oauthService
     * @param OAuthAuthenticationService $authService
     * @param Twig $view
     */
    public function __construct(
        OAuthService $oauthService,
        OAuthAuthenticationService $authService,
        Twig $view
    ) {
        $this->oauthService = $oauthService;
        $this->authService = $authService;
        $this->view = $view;
    }

    /**
     * Redirect to OAuth provider for authentication
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function redirect(Request $request, Response $response, array $args): Response
    {
        $provider = $args['provider'] ?? '';
        
        try {
            $authUrl = $this->oauthService->getAuthorizationUrl($provider);
            $state = $this->oauthService->getState($provider);
            
            // Store state in session for CSRF protection
            $_SESSION['oauth_state'][$provider] = $state;
            
            return $response
                ->withHeader('Location', $authUrl)
                ->withStatus(302);
        } catch (\Exception $e) {
            // Handle error - provider not configured or invalid
            $_SESSION['alerts']['danger'][] = 'OAuth provider not available: ' . $e->getMessage();
            return $response
                ->withHeader('Location', '/login')
                ->withStatus(302);
        }
    }

    /**
     * Handle OAuth callback
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function callback(Request $request, Response $response, array $args): Response
    {
        $provider = $args['provider'] ?? '';
        $queryParams = $request->getQueryParams();
        
        // Verify state for CSRF protection
        $state = $queryParams['state'] ?? '';
        $storedState = $_SESSION['oauth_state'][$provider] ?? '';
        
        if (empty($state) || $state !== $storedState) {
            $_SESSION['alerts']['danger'][] = 'Invalid OAuth state. Please try again.';
            return $response
                ->withHeader('Location', '/login')
                ->withStatus(302);
        }
        
        // Clear stored state
        unset($_SESSION['oauth_state'][$provider]);
        
        // Check for errors
        if (isset($queryParams['error'])) {
            $_SESSION['alerts']['danger'][] = 'OAuth authentication failed: ' . $queryParams['error'];
            return $response
                ->withHeader('Location', '/login')
                ->withStatus(302);
        }
        
        // Exchange code for access token
        $code = $queryParams['code'] ?? '';
        if (empty($code)) {
            $_SESSION['alerts']['danger'][] = 'No authorization code received.';
            return $response
                ->withHeader('Location', '/login')
                ->withStatus(302);
        }
        
        try {
            $oauthProvider = $this->oauthService->getProvider($provider);
            
            // Get access token
            $token = $oauthProvider->getAccessToken('authorization_code', [
                'code' => $code
            ]);
            
            // Get user details
            $resourceOwner = $oauthProvider->getResourceOwner($token);
            $providerUserData = $resourceOwner->toArray();
            
            // Add token data
            $providerUserData['token'] = [
                'access_token' => $token->getToken(),
                'refresh_token' => $token->getRefreshToken(),
                'expires' => $token->getExpires(),
            ];
            
            // Find or create user
            $result = $this->authService->findOrCreateUser($provider, $providerUserData);
            $user = $result['user'];
            $isNewUser = $result['isNewUser'];
            
            // Log in the user
            $_SESSION['user_id'] = $user->id;
            
            // Set success message
            if ($isNewUser) {
                $_SESSION['alerts']['success'][] = 'Welcome! Your account has been created.';
            } else {
                $_SESSION['alerts']['success'][] = 'Successfully logged in with ' . ucfirst($provider) . '.';
            }
            
            // Redirect to dashboard or intended page
            $redirectUrl = $_SESSION['redirect_after_login'] ?? '/dashboard';
            unset($_SESSION['redirect_after_login']);
            
            return $response
                ->withHeader('Location', $redirectUrl)
                ->withStatus(302);
                
        } catch (\Exception $e) {
            $_SESSION['alerts']['danger'][] = 'OAuth authentication error: ' . $e->getMessage();
            return $response
                ->withHeader('Location', '/login')
                ->withStatus(302);
        }
    }

    /**
     * Display login page with OAuth options
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function loginPage(Request $request, Response $response): Response
    {
        $enabledProviders = $this->oauthService->getEnabledProviders();
        
        return $this->view->render($response, 'pages/oauth-login.html.twig', [
            'enabledProviders' => $enabledProviders,
        ]);
    }

    /**
     * Link OAuth provider to current user's account
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function linkProvider(Request $request, Response $response, array $args): Response
    {
        // Check if user is logged in
        if (empty($_SESSION['user_id'])) {
            $_SESSION['alerts']['danger'][] = 'You must be logged in to link an OAuth provider.';
            return $response
                ->withHeader('Location', '/login')
                ->withStatus(302);
        }
        
        $provider = $args['provider'] ?? '';
        
        try {
            $authUrl = $this->oauthService->getAuthorizationUrl($provider);
            $state = $this->oauthService->getState($provider);
            
            // Store state and link flag in session
            $_SESSION['oauth_state'][$provider] = $state;
            $_SESSION['oauth_link_mode'] = true;
            
            return $response
                ->withHeader('Location', $authUrl)
                ->withStatus(302);
        } catch (\Exception $e) {
            $_SESSION['alerts']['danger'][] = 'OAuth provider not available: ' . $e->getMessage();
            return $response
                ->withHeader('Location', '/settings')
                ->withStatus(302);
        }
    }

    /**
     * Disconnect OAuth provider from current user's account
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function disconnect(Request $request, Response $response, array $args): Response
    {
        // Check if user is logged in
        if (empty($_SESSION['user_id'])) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'You must be logged in.',
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }
        
        $provider = $args['provider'] ?? '';
        $userId = $_SESSION['user_id'];
        
        try {
            $connectionRepository = new \UserFrosting\Sprinkle\OAuth\Repository\OAuthConnectionRepository();
            $connection = $connectionRepository->findByUserIdAndProvider($userId, $provider);
            
            if (!$connection) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'OAuth connection not found.',
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }
            
            $connectionRepository->delete($connection);
            
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

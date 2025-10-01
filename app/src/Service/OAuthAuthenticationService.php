<?php

declare(strict_types=1);

/*
 * UserFrosting OAuth Sprinkle (https://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-oauth
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-oauth/blob/master/LICENSE (MIT License)
 */

namespace UserFrosting\Sprinkle\OAuth\Service;

use UserFrosting\Sprinkle\OAuth\Repository\OAuthConnectionRepository;
use UserFrosting\Sprinkle\OAuth\Entity\OAuthConnection;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * OAuth Authentication Service
 * 
 * Handles authentication, user creation, and OAuth connection management
 */
class OAuthAuthenticationService
{
    /**
     * @var OAuthConnectionRepository
     */
    protected OAuthConnectionRepository $connectionRepository;

    /**
     * Constructor
     *
     * @param OAuthConnectionRepository $connectionRepository
     */
    public function __construct(OAuthConnectionRepository $connectionRepository)
    {
        $this->connectionRepository = $connectionRepository;
    }

    /**
     * Find or create user from OAuth provider data
     *
     * @param string $provider Provider name
     * @param array $providerUserData User data from OAuth provider
     * @return array ['user' => User, 'connection' => OAuthConnection, 'isNewUser' => bool]
     */
    public function findOrCreateUser(string $provider, array $providerUserData): array
    {
        $providerId = $this->extractProviderId($provider, $providerUserData);
        $email = $this->extractEmail($provider, $providerUserData);
        
        // Check if OAuth connection already exists
        $connection = $this->connectionRepository->findByProviderAndProviderUserId($provider, $providerId);
        
        if ($connection) {
            // Update existing connection with new tokens
            $this->updateConnection($connection, $providerUserData);
            return [
                'user' => $connection->user,
                'connection' => $connection,
                'isNewUser' => false,
            ];
        }
        
        // Check if user exists by email
        $userClass = 'UserFrosting\Sprinkle\Account\Database\Models\User';
        $user = $userClass::where('email', $email)->first();
        
        $isNewUser = false;
        if (!$user) {
            // Create new user
            $user = $this->createUserFromOAuth($provider, $providerUserData, $email);
            $isNewUser = true;
        }
        
        // Create OAuth connection
        $connection = $this->createConnection($user->id, $provider, $providerId, $providerUserData);
        
        return [
            'user' => $user,
            'connection' => $connection,
            'isNewUser' => $isNewUser,
        ];
    }

    /**
     * Link OAuth provider to existing user
     *
     * @param int $userId User ID
     * @param string $provider Provider name
     * @param array $providerUserData User data from OAuth provider
     * @return OAuthConnection
     */
    public function linkProvider(int $userId, string $provider, array $providerUserData): OAuthConnection
    {
        $providerId = $this->extractProviderId($provider, $providerUserData);
        
        // Check if connection already exists
        $existingConnection = $this->connectionRepository->findByUserIdAndProvider($userId, $provider);
        
        if ($existingConnection) {
            // Update existing connection
            $this->updateConnection($existingConnection, $providerUserData);
            return $existingConnection;
        }
        
        // Create new connection
        return $this->createConnection($userId, $provider, $providerId, $providerUserData);
    }

    /**
     * Create OAuth connection
     *
     * @param int $userId User ID
     * @param string $provider Provider name
     * @param string $providerId Provider user ID
     * @param array $providerUserData Provider user data
     * @return OAuthConnection
     */
    protected function createConnection(
        int $userId,
        string $provider,
        string $providerId,
        array $providerUserData
    ): OAuthConnection {
        return $this->connectionRepository->create([
            'user_id' => $userId,
            'provider' => $provider,
            'provider_user_id' => $providerId,
            'access_token' => $providerUserData['token']['access_token'] ?? '',
            'refresh_token' => $providerUserData['token']['refresh_token'] ?? null,
            'expires_at' => isset($providerUserData['token']['expires'])
                ? date('Y-m-d H:i:s', $providerUserData['token']['expires'])
                : null,
            'user_data' => $providerUserData,
        ]);
    }

    /**
     * Update OAuth connection
     *
     * @param OAuthConnection $connection
     * @param array $providerUserData
     * @return bool
     */
    protected function updateConnection(OAuthConnection $connection, array $providerUserData): bool
    {
        return $this->connectionRepository->update($connection, [
            'access_token' => $providerUserData['token']['access_token'] ?? '',
            'refresh_token' => $providerUserData['token']['refresh_token'] ?? null,
            'expires_at' => isset($providerUserData['token']['expires'])
                ? date('Y-m-d H:i:s', $providerUserData['token']['expires'])
                : null,
            'user_data' => $providerUserData,
        ]);
    }

    /**
     * Create user from OAuth data
     *
     * @param string $provider Provider name
     * @param array $providerUserData Provider user data
     * @param string $email Email address
     * @return mixed User model
     */
    protected function createUserFromOAuth(string $provider, array $providerUserData, string $email)
    {
        $userClass = 'UserFrosting\Sprinkle\Account\Database\Models\User';
        
        $userData = [
            'email' => $email,
            'first_name' => $this->extractFirstName($provider, $providerUserData),
            'last_name' => $this->extractLastName($provider, $providerUserData),
            'user_name' => $this->generateUsername($email),
            'flag_verified' => 1, // OAuth-authenticated users are considered verified
            'flag_enabled' => 1,
        ];
        
        // Generate a random password (user won't need it for OAuth login)
        $userData['password'] = bin2hex(random_bytes(32));
        
        return $userClass::create($userData);
    }

    /**
     * Extract provider user ID
     *
     * @param string $provider Provider name
     * @param array $data Provider user data
     * @return string
     */
    protected function extractProviderId(string $provider, array $data): string
    {
        return (string) ($data['id'] ?? $data['sub'] ?? '');
    }

    /**
     * Extract email from provider data
     *
     * @param string $provider Provider name
     * @param array $data Provider user data
     * @return string
     */
    protected function extractEmail(string $provider, array $data): string
    {
        return $data['email'] ?? '';
    }

    /**
     * Extract first name from provider data
     *
     * @param string $provider Provider name
     * @param array $data Provider user data
     * @return string
     */
    protected function extractFirstName(string $provider, array $data): string
    {
        return $data['given_name'] ?? $data['first_name'] ?? $data['firstName'] ?? '';
    }

    /**
     * Extract last name from provider data
     *
     * @param string $provider Provider name
     * @param array $data Provider user data
     * @return string
     */
    protected function extractLastName(string $provider, array $data): string
    {
        return $data['family_name'] ?? $data['last_name'] ?? $data['lastName'] ?? '';
    }

    /**
     * Generate username from email
     *
     * @param string $email Email address
     * @return string
     */
    protected function generateUsername(string $email): string
    {
        $username = explode('@', $email)[0];
        $username = preg_replace('/[^a-zA-Z0-9_]/', '', $username);
        
        // Check if username exists and append number if needed
        $userClass = 'UserFrosting\Sprinkle\Account\Database\Models\User';
        $originalUsername = $username;
        $counter = 1;
        
        while ($userClass::where('user_name', $username)->exists()) {
            $username = $originalUsername . $counter;
            $counter++;
        }
        
        return $username;
    }
}

<?php

declare(strict_types=1);

/*
 * UserFrosting OAuth Sprinkle (https://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-oauth
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-oauth/blob/master/LICENSE (MIT License)
 */

namespace UserFrosting\Sprinkle\OAuth\Authenticator;

use UserFrosting\Sprinkle\Account\Database\Models\Interfaces\UserInterface;
use UserFrosting\Sprinkle\OAuth\Database\Models\Interfaces\OAuthConnectionInterface;
use UserFrosting\Sprinkle\OAuth\Repository\OAuthConnectionRepository;

/**
 * OAuth Authenticator.
 *
 * Handles OAuth authentication flow, user creation, and OAuth connection management.
 * Supports multiple OAuth providers (Google, Facebook, LinkedIn, Microsoft).
 */
class OAuthAuthenticator
{
    /**
     * Constructor.
     *
     * @param OAuthConnectionRepository $connectionRepository OAuth connection repository
     * @param string                    $userModel           User model class name (from DI)
     */
    public function __construct(
        protected OAuthConnectionRepository $connectionRepository,
        protected string $userModel = UserInterface::class
    ) {
    }

    /**
     * Find or create user from OAuth provider data.
     *
     * Checks if an OAuth connection already exists for the provider and provider user ID.
     * If not, checks if a user exists with the same email and creates a connection.
     * If no user exists, creates a new user and connection.
     *
     * @param string               $provider         Provider name (google, facebook, linkedin, microsoft)
     * @param array<string, mixed> $providerUserData User data from OAuth provider
     *
     * @return array{user: UserInterface, connection: OAuthConnectionInterface, isNewUser: bool}
     */
    public function findOrCreateUser(string $provider, array $providerUserData): array
    {
        $providerId = $this->extractProviderId($provider, $providerUserData);
        $email = $this->extractEmail($provider, $providerUserData);

        // Check if OAuth connection already exists
        $connection = $this->connectionRepository->findByProviderAndProviderUserId($provider, $providerId);

        if ($connection !== null) {
            // Update existing connection with new tokens
            $this->updateConnection($connection, $providerUserData);

            return [
                'user' => $connection->user,
                'connection' => $connection,
                'isNewUser' => false,
            ];
        }

        // Check if user exists by email
        $user = $this->userModel::where('email', $email)->first();

        $isNewUser = false;
        if ($user === null) {
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
     * Link OAuth provider to existing user.
     *
     * Allows authenticated users to link additional OAuth providers to their account.
     * If a connection already exists for this user and provider, updates the tokens.
     * Otherwise, creates a new connection.
     *
     * @param int                  $userId           User ID
     * @param string               $provider         Provider name (google, facebook, linkedin, microsoft)
     * @param array<string, mixed> $providerUserData User data from OAuth provider
     *
     * @return OAuthConnectionInterface
     */
    public function linkProvider(int $userId, string $provider, array $providerUserData): OAuthConnectionInterface
    {
        $providerId = $this->extractProviderId($provider, $providerUserData);

        // Check if connection already exists
        $existingConnection = $this->connectionRepository->findByUserIdAndProvider($userId, $provider);

        if ($existingConnection !== null) {
            // Update existing connection
            $this->updateConnection($existingConnection, $providerUserData);

            return $existingConnection;
        }

        // Create new connection
        return $this->createConnection($userId, $provider, $providerId, $providerUserData);
    }

    /**
     * Create OAuth connection.
     *
     * @param int                  $userId           User ID
     * @param string               $provider         Provider name
     * @param string               $providerId       Provider user ID
     * @param array<string, mixed> $providerUserData Provider user data
     *
     * @return OAuthConnectionInterface
     */
    protected function createConnection(
        int $userId,
        string $provider,
        string $providerId,
        array $providerUserData
    ): OAuthConnectionInterface {
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
     * Update OAuth connection with new token data.
     *
     * @param OAuthConnectionInterface $connection       OAuth connection to update
     * @param array<string, mixed>     $providerUserData Provider user data with new tokens
     *
     * @return bool True if update was successful
     */
    protected function updateConnection(OAuthConnectionInterface $connection, array $providerUserData): bool
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
     * Create user from OAuth data.
     *
     * Creates a new UserFrosting user account based on OAuth provider data.
     * OAuth-authenticated users are automatically verified and enabled.
     * A random password is generated as the user will authenticate via OAuth.
     *
     * @param string               $provider         Provider name
     * @param array<string, mixed> $providerUserData Provider user data
     * @param string               $email            Email address
     *
     * @return UserInterface Created user model
     */
    protected function createUserFromOAuth(string $provider, array $providerUserData, string $email): UserInterface
    {
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

        return $this->userModel::create($userData);
    }

    /**
     * Extract provider user ID from OAuth data.
     *
     * Different providers use different field names for user ID (id, sub, etc.).
     *
     * @param string               $provider Provider name
     * @param array<string, mixed> $data     Provider user data
     *
     * @return string Provider user ID
     */
    protected function extractProviderId(string $provider, array $data): string
    {
        return (string) ($data['id'] ?? $data['sub'] ?? '');
    }

    /**
     * Extract email from provider data.
     *
     * @param string               $provider Provider name
     * @param array<string, mixed> $data     Provider user data
     *
     * @return string Email address
     */
    protected function extractEmail(string $provider, array $data): string
    {
        return $data['email'] ?? '';
    }

    /**
     * Extract first name from provider data.
     *
     * Different providers use different field names (given_name, first_name, firstName).
     *
     * @param string               $provider Provider name
     * @param array<string, mixed> $data     Provider user data
     *
     * @return string First name
     */
    protected function extractFirstName(string $provider, array $data): string
    {
        return $data['given_name'] ?? $data['first_name'] ?? $data['firstName'] ?? '';
    }

    /**
     * Extract last name from provider data.
     *
     * Different providers use different field names (family_name, last_name, lastName).
     *
     * @param string               $provider Provider name
     * @param array<string, mixed> $data     Provider user data
     *
     * @return string Last name
     */
    protected function extractLastName(string $provider, array $data): string
    {
        return $data['family_name'] ?? $data['last_name'] ?? $data['lastName'] ?? '';
    }

    /**
     * Generate unique username from email.
     *
     * Extracts username from email and sanitizes it to alphanumeric characters.
     * If username already exists, appends a number to make it unique.
     *
     * @param string $email Email address
     *
     * @return string Generated unique username
     */
    protected function generateUsername(string $email): string
    {
        $username = explode('@', $email)[0];
        $username = preg_replace('/[^a-zA-Z0-9_]/', '', $username);

        // Check if username exists and append number if needed
        $originalUsername = $username;
        $counter = 1;

        while ($this->userModel::where('user_name', $username)->exists()) {
            $username = $originalUsername . $counter;
            $counter++;
        }

        return $username;
    }
}

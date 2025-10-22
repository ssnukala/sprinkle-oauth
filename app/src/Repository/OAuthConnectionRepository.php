<?php

declare(strict_types=1);

/*
 * UserFrosting OAuth Sprinkle (https://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-oauth
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-oauth/blob/master/LICENSE (MIT License)
 */

namespace UserFrosting\Sprinkle\OAuth\Repository;

use Illuminate\Database\Eloquent\Collection;
use UserFrosting\Sprinkle\OAuth\Database\Models\Interfaces\OAuthConnectionInterface;
use UserFrosting\Sprinkle\OAuth\Database\Models\OAuthConnection;

/**
 * OAuth Connection Repository.
 *
 * Provides data access methods for managing OAuth provider connections.
 * Follows UserFrosting 6 repository pattern for consistent data access.
 */
class OAuthConnectionRepository
{
    /**
     * Find OAuth connection by provider and provider user ID.
     *
     * Used during OAuth callback to check if a connection already exists
     * for the authenticated provider user.
     *
     * @param string $provider       Provider name (google, facebook, linkedin, microsoft)
     * @param string $providerUserId User ID from the OAuth provider
     *
     * @return OAuthConnectionInterface|null OAuth connection or null if not found
     */
    public function findByProviderAndProviderUserId(string $provider, string $providerUserId): ?OAuthConnectionInterface
    {
        return OAuthConnection::where('provider', $provider)
            ->where('provider_user_id', $providerUserId)
            ->first();
    }

    /**
     * Find all OAuth connections for a user.
     *
     * Returns all OAuth provider connections linked to a specific user account.
     * Useful for displaying linked accounts in user settings.
     *
     * @param int $userId UserFrosting user ID
     *
     * @return Collection<int, OAuthConnectionInterface> Collection of OAuth connections
     */
    public function findByUserId(int $userId): Collection
    {
        return OAuthConnection::where('user_id', $userId)->get();
    }

    /**
     * Find OAuth connection by user and provider.
     *
     * Checks if a specific user has a connection to a specific OAuth provider.
     * Used when linking new providers to prevent duplicates.
     *
     * @param int    $userId   UserFrosting user ID
     * @param string $provider Provider name (google, facebook, linkedin, microsoft)
     *
     * @return OAuthConnectionInterface|null OAuth connection or null if not found
     */
    public function findByUserIdAndProvider(int $userId, string $provider): ?OAuthConnectionInterface
    {
        return OAuthConnection::where('user_id', $userId)
            ->where('provider', $provider)
            ->first();
    }

    /**
     * Create a new OAuth connection.
     *
     * @param array<string, mixed> $data Connection data including user_id, provider, tokens
     *
     * @return OAuthConnectionInterface Created OAuth connection
     */
    public function create(array $data): OAuthConnectionInterface
    {
        return OAuthConnection::create($data);
    }

    /**
     * Update an existing OAuth connection.
     *
     * Typically used to refresh OAuth tokens when they expire.
     *
     * @param OAuthConnectionInterface $connection OAuth connection to update
     * @param array<string, mixed>     $data       Updated connection data
     *
     * @return bool True if update was successful
     */
    public function update(OAuthConnectionInterface $connection, array $data): bool
    {
        return $connection->update($data);
    }

    /**
     * Delete an OAuth connection.
     *
     * Removes the link between a user and an OAuth provider.
     * User can still log in with password or other linked providers.
     *
     * @param OAuthConnectionInterface $connection OAuth connection to delete
     *
     * @return bool True if deletion was successful
     */
    public function delete(OAuthConnectionInterface $connection): bool
    {
        return $connection->delete();
    }
}

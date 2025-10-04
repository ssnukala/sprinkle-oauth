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

use UserFrosting\Sprinkle\OAuth\Database\Models\OAuthConnection;

/**
 * OAuth Connection Repository
 * 
 * Provides methods for managing OAuth connections
 */
class OAuthConnectionRepository
{
    /**
     * Find OAuth connection by provider and provider user ID
     *
     * @param string $provider Provider name (google, facebook, etc.)
     * @param string $providerUserId User ID from the provider
     * @return OAuthConnection|null
     */
    public function findByProviderAndProviderUserId(string $provider, string $providerUserId): ?OAuthConnection
    {
        return OAuthConnection::where('provider', $provider)
            ->where('provider_user_id', $providerUserId)
            ->first();
    }

    /**
     * Find all OAuth connections for a user
     *
     * @param int $userId UserFrosting user ID
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findByUserId(int $userId)
    {
        return OAuthConnection::where('user_id', $userId)->get();
    }

    /**
     * Find OAuth connection by user and provider
     *
     * @param int $userId UserFrosting user ID
     * @param string $provider Provider name
     * @return OAuthConnection|null
     */
    public function findByUserIdAndProvider(int $userId, string $provider): ?OAuthConnection
    {
        return OAuthConnection::where('user_id', $userId)
            ->where('provider', $provider)
            ->first();
    }

    /**
     * Create a new OAuth connection
     *
     * @param array $data Connection data
     * @return OAuthConnection
     */
    public function create(array $data): OAuthConnection
    {
        return OAuthConnection::create($data);
    }

    /**
     * Update an existing OAuth connection
     *
     * @param OAuthConnection $connection
     * @param array $data
     * @return bool
     */
    public function update(OAuthConnection $connection, array $data): bool
    {
        return $connection->update($data);
    }

    /**
     * Delete an OAuth connection
     *
     * @param OAuthConnection $connection
     * @return bool
     */
    public function delete(OAuthConnection $connection): bool
    {
        return $connection->delete();
    }
}

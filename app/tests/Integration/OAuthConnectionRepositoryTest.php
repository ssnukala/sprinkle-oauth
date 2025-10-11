<?php

declare(strict_types=1);

/*
 * UserFrosting OAuth Sprinkle (https://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-oauth
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-oauth/blob/master/LICENSE (MIT License)
 */

namespace UserFrosting\Sprinkle\OAuth\Tests\Integration;

use UserFrosting\Sprinkle\OAuth\Tests\OAuthTestCase;
use UserFrosting\Sprinkle\OAuth\Repository\OAuthConnectionRepository;
use UserFrosting\Sprinkle\OAuth\Database\Models\OAuthConnection;
use UserFrosting\Sprinkle\Account\Database\Models\User;

/**
 * Integration tests for OAuthConnectionRepository
 * 
 * Tests the repository's ability to manage OAuth connections
 * with the database layer
 */
class OAuthConnectionRepositoryTest extends OAuthTestCase
{
    /**
     * Set up test database
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Run migrations
        $this->refreshDatabase();
    }

    /**
     * Test that the repository can be retrieved from the container
     */
    public function testRepositoryCanBeRetrievedFromContainer(): void
    {
        $repository = $this->ci->get(OAuthConnectionRepository::class);
        
        $this->assertInstanceOf(OAuthConnectionRepository::class, $repository);
    }

    /**
     * Test creating a new OAuth connection
     */
    public function testCanCreateOAuthConnection(): void
    {
        // Create a test user first
        $user = User::factory()->create();

        $repository = $this->ci->get(OAuthConnectionRepository::class);
        
        $connection = $repository->create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_user_id' => '123456789',
            'access_token' => 'test-access-token',
            'refresh_token' => 'test-refresh-token',
            'expires_at' => now()->addHour(),
        ]);

        $this->assertInstanceOf(OAuthConnection::class, $connection);
        $this->assertEquals('google', $connection->provider);
        $this->assertEquals('123456789', $connection->provider_user_id);
        $this->assertEquals($user->id, $connection->user_id);
    }

    /**
     * Test finding OAuth connection by provider and provider user ID
     */
    public function testCanFindByProviderAndProviderUserId(): void
    {
        $user = User::factory()->create();
        
        // Create a connection
        OAuthConnection::create([
            'user_id' => $user->id,
            'provider' => 'facebook',
            'provider_user_id' => '987654321',
            'access_token' => 'test-token',
        ]);

        $repository = $this->ci->get(OAuthConnectionRepository::class);
        $connection = $repository->findByProviderAndProviderUserId('facebook', '987654321');

        $this->assertNotNull($connection);
        $this->assertEquals('facebook', $connection->provider);
        $this->assertEquals('987654321', $connection->provider_user_id);
    }

    /**
     * Test finding all connections for a user
     */
    public function testCanFindAllConnectionsForUser(): void
    {
        $user = User::factory()->create();
        
        // Create multiple connections for the same user
        OAuthConnection::create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_user_id' => '111',
            'access_token' => 'token1',
        ]);
        
        OAuthConnection::create([
            'user_id' => $user->id,
            'provider' => 'facebook',
            'provider_user_id' => '222',
            'access_token' => 'token2',
        ]);

        $repository = $this->ci->get(OAuthConnectionRepository::class);
        $connections = $repository->findByUserId($user->id);

        $this->assertCount(2, $connections);
        $this->assertTrue($connections->contains('provider', 'google'));
        $this->assertTrue($connections->contains('provider', 'facebook'));
    }

    /**
     * Test finding connection by user ID and provider
     */
    public function testCanFindByUserIdAndProvider(): void
    {
        $user = User::factory()->create();
        
        OAuthConnection::create([
            'user_id' => $user->id,
            'provider' => 'linkedin',
            'provider_user_id' => '333',
            'access_token' => 'token3',
        ]);

        $repository = $this->ci->get(OAuthConnectionRepository::class);
        $connection = $repository->findByUserIdAndProvider($user->id, 'linkedin');

        $this->assertNotNull($connection);
        $this->assertEquals('linkedin', $connection->provider);
        $this->assertEquals($user->id, $connection->user_id);
    }

    /**
     * Test updating an OAuth connection
     */
    public function testCanUpdateOAuthConnection(): void
    {
        $user = User::factory()->create();
        
        $connection = OAuthConnection::create([
            'user_id' => $user->id,
            'provider' => 'microsoft',
            'provider_user_id' => '444',
            'access_token' => 'old-token',
        ]);

        $repository = $this->ci->get(OAuthConnectionRepository::class);
        $updated = $repository->update($connection, [
            'access_token' => 'new-token',
            'refresh_token' => 'new-refresh-token',
        ]);

        $this->assertTrue($updated);
        
        $connection->refresh();
        $this->assertEquals('new-token', $connection->access_token);
        $this->assertEquals('new-refresh-token', $connection->refresh_token);
    }

    /**
     * Test deleting an OAuth connection
     */
    public function testCanDeleteOAuthConnection(): void
    {
        $user = User::factory()->create();
        
        $connection = OAuthConnection::create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_user_id' => '555',
            'access_token' => 'token',
        ]);

        $repository = $this->ci->get(OAuthConnectionRepository::class);
        $deleted = $repository->delete($connection);

        $this->assertTrue($deleted);
        $this->assertNull(
            $repository->findByProviderAndProviderUserId('google', '555')
        );
    }

    /**
     * Test that connections are isolated by provider
     */
    public function testConnectionsAreIsolatedByProvider(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        // Same provider, different users
        OAuthConnection::create([
            'user_id' => $user1->id,
            'provider' => 'google',
            'provider_user_id' => '666',
            'access_token' => 'token1',
        ]);
        
        OAuthConnection::create([
            'user_id' => $user2->id,
            'provider' => 'google',
            'provider_user_id' => '777',
            'access_token' => 'token2',
        ]);

        $repository = $this->ci->get(OAuthConnectionRepository::class);
        
        $connection1 = $repository->findByUserIdAndProvider($user1->id, 'google');
        $connection2 = $repository->findByUserIdAndProvider($user2->id, 'google');

        $this->assertNotNull($connection1);
        $this->assertNotNull($connection2);
        $this->assertNotEquals($connection1->id, $connection2->id);
        $this->assertEquals('666', $connection1->provider_user_id);
        $this->assertEquals('777', $connection2->provider_user_id);
    }
}

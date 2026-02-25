<?php

declare(strict_types=1);

/*
 * UserFrosting OAuth Sprinkle (https://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-oauth
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-oauth/blob/master/LICENSE (MIT License)
 */

namespace UserFrosting\Sprinkle\OAuth;

use UserFrosting\Sprinkle\Account\Account;
use UserFrosting\Sprinkle\Core\Core;
use UserFrosting\Sprinkle\Core\Sprinkle\Recipe\MigrationRecipe;
use UserFrosting\Sprinkle\SprinkleRecipe;
use UserFrosting\Sprinkle\OAuth\Database\Migrations\CreateOAuthConnectionsTable;
use UserFrosting\Sprinkle\OAuth\Routes\OAuthRoutes;
use UserFrosting\Sprinkle\OAuth\ServicesProvider\OAuthServicesProvider;

/**
 * OAuth Sprinkle Recipe
 *
 * Provides OAuth authentication for UserFrosting using multiple providers:
 * - Google
 * - Meta (Facebook, Instagram)
 * - Microsoft (Outlook)
 * - LinkedIn
 */
class OAuth implements SprinkleRecipe, MigrationRecipe
{
    /**
     * Returns sprinkle name
     */
    public function getName(): string
    {
        return 'OAuth Sprinkle';
    }

    /**
     * Returns sprinkle directory path
     */
    public function getPath(): string
    {
        return __DIR__ . '/../';
    }

    /**
     * Returns routes definition classes
     */
    public function getRoutes(): array
    {
        return [
            OAuthRoutes::class,
        ];
    }

    /**
     * Returns dependent sprinkles
     */
    public function getSprinkles(): array
    {
        return [
            Core::class,
            Account::class,
        ];
    }

    /**
     * Returns service providers
     */
    public function getServices(): array
    {
        return [
            OAuthServicesProvider::class,
        ];
    }

    /**
     * Returns migrations
     */
    public function getMigrations(): array
    {
        return [
            CreateOAuthConnectionsTable::class,
        ];
    }
}

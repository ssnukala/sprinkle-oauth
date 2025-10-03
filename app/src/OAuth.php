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

use UserFrosting\Sprinkle\Core\Core;
use UserFrosting\Sprinkle\SprinkleRecipe;
use UserFrosting\Sprinkle\OAuth\Routes\OAuthRoutes;
use UserFrosting\Sprinkle\OAuth\ServicesProvider\OAuthServicesProvider;
use UserFrosting\Sprinkle\OAuth\ServicesProvider\OAuthControllerProvider;

/**
 * OAuth Sprinkle Recipe
 * 
 * Provides OAuth authentication for UserFrosting using multiple providers:
 * - Google
 * - Meta (Facebook, Instagram)
 * - Microsoft (Outlook)
 * - LinkedIn
 */
class OAuth implements SprinkleRecipe
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
     * Returns service providers
     */
    public function getSprinkles(): array
    {
        return [
            Core::class,
        ];
    }

    /**
     * Returns service providers
     */
    public function getServices(): array
    {
        return [
            OAuthServicesProvider::class,
            OAuthControllerProvider::class,
        ];
    }
}

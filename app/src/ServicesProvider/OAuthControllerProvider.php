<?php

declare(strict_types=1);

/*
 * UserFrosting OAuth Sprinkle (https://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-oauth
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-oauth/blob/master/LICENSE (MIT License)
 */

namespace UserFrosting\Sprinkle\OAuth\ServicesProvider;

use UserFrosting\Sprinkle\OAuth\Controller\OAuthController;
use UserFrosting\ServicesProvider\ServicesProviderInterface;

/**
 * OAuth Controller Provider.
 *
 * Registers OAuth controllers in the dependency injection container.
 * Controllers are autowired with their dependencies.
 */
class OAuthControllerProvider implements ServicesProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function register(): array
    {
        return [
            // OAuth Controller
            // Handles OAuth redirect, callback, and linking flows
            OAuthController::class => \DI\autowire(),
        ];
    }
}

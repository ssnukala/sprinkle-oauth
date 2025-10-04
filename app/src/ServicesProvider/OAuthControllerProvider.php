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
 * OAuth Controller Provider
 * 
 * Registers OAuth controllers in the DI container
 */
class OAuthControllerProvider implements ServicesProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(): array
    {
        return [
            // OAuth Controller
            OAuthController::class => \DI\autowire(),
        ];
    }
}

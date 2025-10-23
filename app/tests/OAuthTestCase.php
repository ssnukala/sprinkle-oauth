<?php

declare(strict_types=1);

/*
 * UserFrosting OAuth Sprinkle (https://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-oauth
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-oauth/blob/master/LICENSE (MIT License)
 */

namespace UserFrosting\Sprinkle\OAuth\Tests;

use UserFrosting\Sprinkle\OAuth\OAuth;
use UserFrosting\Testing\TestCase;

/**
 * Test case with OAuth as main sprinkle
 * 
 * This base test case provides a testing environment with the OAuth sprinkle
 * configured as the main sprinkle. All integration tests should extend this class.
 */
class OAuthTestCase extends TestCase
{
    /**
     * @var string The main sprinkle class for testing
     */
    protected string $mainSprinkle = OAuth::class;
}

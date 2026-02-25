<?php

declare(strict_types=1);

/*
 * UserFrosting OAuth Sprinkle (https://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-oauth
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-oauth/blob/master/LICENSE (MIT License)
 */

/**
 * OAuth Configuration
 * 
 * Configure OAuth providers here. Set clientId and clientSecret for each provider.
 * To enable a provider, set valid credentials. Leave empty to disable.
 */
return [
    'oauth' => [
        'google' => [
            'clientId' => getenv('OAUTH_GOOGLE_CLIENT_ID') ?: '',
            'clientSecret' => getenv('OAUTH_GOOGLE_CLIENT_SECRET') ?: '',
            'scopes' => [],            // Additional scopes beyond email/profile/openid (e.g., ['https://www.googleapis.com/auth/spreadsheets'])
            'access_type' => 'online', // Set to 'offline' for refresh tokens (needed for Google Sheets)
        ],
        'facebook' => [
            'clientId' => getenv('OAUTH_FACEBOOK_CLIENT_ID') ?: '',
            'clientSecret' => getenv('OAUTH_FACEBOOK_CLIENT_SECRET') ?: '',
            'graphApiVersion' => 'v12.0',
        ],
        'linkedin' => [
            'clientId' => getenv('OAUTH_LINKEDIN_CLIENT_ID') ?: '',
            'clientSecret' => getenv('OAUTH_LINKEDIN_CLIENT_SECRET') ?: '',
        ],
        'microsoft' => [
            'clientId' => getenv('OAUTH_MICROSOFT_CLIENT_ID') ?: '',
            'clientSecret' => getenv('OAUTH_MICROSOFT_CLIENT_SECRET') ?: '',
        ],
    ],
];

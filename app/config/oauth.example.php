<?php

declare(strict_types=1);

/*
 * Example OAuth Configuration
 * 
 * This file demonstrates all available configuration options for the OAuth sprinkle.
 * Copy relevant sections to your app's config file to customize behavior.
 */

return [
    'oauth' => [
        // Google OAuth Configuration
        'google' => [
            // Required: OAuth client credentials
            'clientId' => getenv('OAUTH_GOOGLE_CLIENT_ID') ?: '',
            'clientSecret' => getenv('OAUTH_GOOGLE_CLIENT_SECRET') ?: '',
            
            // Optional: Custom scopes
            // Default: ['openid', 'email', 'profile']
            // 'scopes' => ['openid', 'email', 'profile', 'https://www.googleapis.com/auth/calendar.readonly'],
            
            // Optional: Access type for refresh tokens
            // 'accessType' => 'offline',
            
            // Optional: Hosted domain restriction
            // 'hostedDomain' => 'yourdomain.com', // Only allow users from this domain
        ],

        // Facebook OAuth Configuration
        'facebook' => [
            // Required: OAuth client credentials
            'clientId' => getenv('OAUTH_FACEBOOK_CLIENT_ID') ?: '',
            'clientSecret' => getenv('OAUTH_FACEBOOK_CLIENT_SECRET') ?: '',
            
            // Required: Graph API version
            'graphApiVersion' => 'v12.0',
            
            // Optional: Custom scopes
            // Default: ['email', 'public_profile']
            // 'scopes' => ['email', 'public_profile', 'user_birthday'],
        ],

        // LinkedIn OAuth Configuration
        'linkedin' => [
            // Required: OAuth client credentials
            'clientId' => getenv('OAUTH_LINKEDIN_CLIENT_ID') ?: '',
            'clientSecret' => getenv('OAUTH_LINKEDIN_CLIENT_SECRET') ?: '',
            
            // Optional: Custom scopes
            // Default: ['r_liteprofile', 'r_emailaddress']
            // Note: LinkedIn requires product approval for extended scopes
            // 'scopes' => ['r_liteprofile', 'r_emailaddress', 'w_member_social'],
        ],

        // Microsoft OAuth Configuration
        'microsoft' => [
            // Required: OAuth client credentials
            'clientId' => getenv('OAUTH_MICROSOFT_CLIENT_ID') ?: '',
            'clientSecret' => getenv('OAUTH_MICROSOFT_CLIENT_SECRET') ?: '',
            
            // Optional: Custom scopes
            // Default: ['openid', 'email', 'profile']
            // 'scopes' => ['openid', 'email', 'profile', 'User.Read', 'Calendars.Read'],
            
            // Optional: Tenant ID (for organization-specific apps)
            // 'tenant' => 'common', // Options: 'common', 'organizations', 'consumers', or specific tenant ID
        ],
    ],

    // Optional: Site configuration (usually in main config)
    'site' => [
        'uri' => [
            'public' => getenv('APP_URL') ?: 'http://localhost',
        ],
    ],

    // Optional: Session configuration
    'session' => [
        'name' => 'uf_session',
        'minutes' => 120,
        'secure' => true,
        'httponly' => true,
        'samesite' => 'lax',
    ],
];

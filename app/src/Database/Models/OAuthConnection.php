<?php

declare(strict_types=1);

/*
 * UserFrosting OAuth Sprinkle (https://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-oauth
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-oauth/blob/master/LICENSE (MIT License)
 */

namespace UserFrosting\Sprinkle\OAuth\Database\Models;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;

/**
 * OAuth Connection Entity
 * 
 * Represents a connection between a UserFrosting user and an OAuth provider.
 * Allows multiple OAuth providers to be associated with a single user account.
 *
 * @property int $id
 * @property int $user_id
 * @property string $provider OAuth provider name (google, facebook, microsoft, linkedin)
 * @property string $provider_user_id User ID from the OAuth provider
 * @property string $access_token
 * @property string|null $refresh_token
 * @property \DateTime $expires_at
 * @property array $user_data JSON data from provider
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 */
class OAuthConnection extends Model
{
    /**
     * The name of the table for the current model.
     */
    protected $table = 'oauth_connections';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'provider',
        'provider_user_id',
        'access_token',
        'refresh_token',
        'expires_at',
        'user_data',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'user_id' => 'integer',
        'user_data' => 'array',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for arrays.
     */
    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    /**
     * Get the user that owns the OAuth connection.
     */
    public function user()
    {
        return $this->belongsTo('UserFrosting\Sprinkle\Account\Database\Models\User', 'user_id');
    }
}

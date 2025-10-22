<?php

declare(strict_types=1);

/*
 * UserFrosting OAuth Sprinkle (https://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-oauth
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-oauth/blob/master/LICENSE (MIT License)
 */

namespace UserFrosting\Sprinkle\OAuth\Database\Models\Interfaces;

use DateTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\Builder as QueryBuilder;
use UserFrosting\Sprinkle\Account\Database\Models\Interfaces\UserInterface;

/**
 * OAuth Connection Model Interface.
 *
 * Represents the interface for OAuth provider connections.
 * Allows multiple OAuth providers to be associated with a single user account.
 *
 * @mixin \Illuminate\Database\Eloquent\Builder
 * @mixin \Illuminate\Database\Eloquent\Model
 * @mixin \UserFrosting\Sprinkle\Core\Database\Models\Model
 *
 * @property int           $id
 * @property int           $user_id
 * @property string        $provider OAuth provider name (google, facebook, microsoft, linkedin)
 * @property string        $provider_user_id User ID from the OAuth provider
 * @property string        $access_token OAuth access token
 * @property string|null   $refresh_token OAuth refresh token
 * @property DateTime|null $expires_at Token expiration timestamp
 * @property array         $user_data JSON data from provider
 * @property DateTime      $created_at Record creation timestamp
 * @property DateTime      $updated_at Record update timestamp
 * @property UserInterface $user The user that owns this OAuth connection
 *
 * @method        $this notExpired()
 * @method static $this notExpired()
 * @method        $this forProvider(string $provider)
 * @method static $this forProvider(string $provider)
 * @method        $this joinUser()
 * @method static $this joinUser()
 */
interface OAuthConnectionInterface
{
    /**
     * Get the user that owns the OAuth connection.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo;

    /**
     * Scope a query to only include connections that are not expired.
     *
     * @param Builder $query
     *
     * @return Builder|QueryBuilder
     */
    public function scopeNotExpired(Builder $query): Builder|QueryBuilder;

    /**
     * Scope a query to only include connections for a specific provider.
     *
     * @param Builder $query
     * @param string  $provider Provider name (google, facebook, microsoft, linkedin)
     *
     * @return Builder|QueryBuilder
     */
    public function scopeForProvider(Builder $query, string $provider): Builder|QueryBuilder;

    /**
     * Joins the connection's user, for operations like sorting, searching, and pagination.
     *
     * @param Builder $query
     *
     * @return Builder|QueryBuilder
     */
    public function scopeJoinUser(Builder $query): Builder|QueryBuilder;
}

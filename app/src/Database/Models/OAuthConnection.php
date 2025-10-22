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

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\Builder as QueryBuilder;
use UserFrosting\Sprinkle\Account\Database\Models\Interfaces\UserInterface;
use UserFrosting\Sprinkle\Core\Database\Models\Model;
use UserFrosting\Sprinkle\OAuth\Database\Models\Interfaces\OAuthConnectionInterface;

/**
 * OAuth Connection Model.
 *
 * Represents a connection between a UserFrosting user and an OAuth provider.
 * Allows multiple OAuth providers to be associated with a single user account.
 *
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class OAuthConnection extends Model implements OAuthConnectionInterface
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
     * {@inheritDoc}
     */
    public function user(): BelongsTo
    {
        /** @var string */
        $relation = static::$ci?->get(UserInterface::class);

        return $this->belongsTo($relation);
    }

    /**
     * {@inheritDoc}
     */
    public function scopeNotExpired(Builder $query): Builder|QueryBuilder
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', Carbon::now());
        });
    }

    /**
     * {@inheritDoc}
     */
    public function scopeForProvider(Builder $query, string $provider): Builder|QueryBuilder
    {
        return $query->where('provider', $provider);
    }

    /**
     * {@inheritDoc}
     */
    public function scopeJoinUser(Builder $query): Builder|QueryBuilder
    {
        /** @var string */
        $userTable = static::$ci?->get(UserInterface::class)::TABLE ?? 'users';

        return $query->join($userTable, function ($join) use ($userTable) {
            $join->on($this->getTable() . '.user_id', '=', $userTable . '.id');
        });
    }
}

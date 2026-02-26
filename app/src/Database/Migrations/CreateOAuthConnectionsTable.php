<?php

declare(strict_types=1);

/*
 * UserFrosting OAuth Sprinkle (https://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-oauth
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-oauth/blob/master/LICENSE (MIT License)
 */

namespace UserFrosting\Sprinkle\OAuth\Database\Migrations;

use Illuminate\Database\Schema\Blueprint;
use UserFrosting\Sprinkle\Core\Database\Migration;

/**
 * OAuth Connections Table Migration.
 *
 * Creates the oauth_connections table to store OAuth provider connections for users.
 * This table maintains a many-to-one relationship with users (each user can have multiple OAuth connections).
 * Supports multiple OAuth providers: Google, Facebook, LinkedIn, Microsoft.
 *
 * Design Note: This is a separate table from 'persistences' because OAuth connections
 * serve a different purpose than session persistence. See PERSISTENCE_INTEGRATION_ANALYSIS.md
 * for detailed rationale.
 */
class CreateOAuthConnectionsTable extends Migration
{
    /**
     * {@inheritDoc}
     */
    public static $dependencies = [];

    /**
     * {@inheritDoc}
     */
    public function up(): void
    {
        if (!$this->schema->hasTable('oauth_connections')) {
            $this->schema->create('oauth_connections', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user_id')->unsigned()->index();
                $table->string('provider', 50)->index(); // google, facebook, microsoft, linkedin
                $table->string('provider_user_id', 255)->index(); // OAuth provider's user ID
                $table->text('access_token');
                $table->text('refresh_token')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->json('user_data')->nullable(); // Store provider user data
                $table->timestamps();

                // Unique constraint to prevent duplicate connections
                $table->unique(['user_id', 'provider', 'provider_user_id'], 'unique_user_provider');
                
                // Foreign key to users table
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    /**
     * {@inheritDoc}
     */
    public function down(): void
    {
        $this->schema->dropIfExists('oauth_connections');
    }
}

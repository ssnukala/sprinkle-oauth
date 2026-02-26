<!--
  OAuth Sprinkle (https://www.userfrosting.com)

  @link      https://github.com/ssnukala/sprinkle-oauth
  @copyright Copyright (c) 2026 Srinivas Nukala
  @license   https://github.com/ssnukala/sprinkle-oauth/blob/master/LICENSE.md (MIT License)
-->

<!-- OAuthConnections.vue -->
<!-- Displays OAuth provider connection status and allows connecting/disconnecting providers.
     Uses popup-based OAuth flow via the useOAuth composable for a seamless experience. -->
<template>
    <div class="oauth-connections-component">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">OAuth Connections</h3>
            </div>
            <div class="card-body">
                <p class="text-muted">Connect or disconnect OAuth providers for your account</p>

                <!-- Error alert -->
                <div v-if="oauth.error.value" class="alert alert-danger" role="alert">
                    {{ oauth.error.value }}
                </div>

                <div class="list-group">
                    <div
                        v-for="provider in oauth.providers.value"
                        :key="provider.id"
                        class="list-group-item d-flex justify-content-between align-items-center"
                    >
                        <div>
                            <i :class="[provider.icon, 'fa-lg', 'mr-2']" :style="{ color: provider.color }"></i>
                            <strong>{{ provider.name }}</strong>
                        </div>
                        <div class="d-flex align-items-center">
                            <span v-if="oauth.isConnected(provider.id)" class="badge badge-success mr-2">
                                Connected
                            </span>

                            <!-- Disconnect button -->
                            <button
                                v-if="oauth.isConnected(provider.id)"
                                class="btn btn-sm btn-danger"
                                :disabled="oauth.loading.value && oauth.activeProvider.value === provider.id"
                                @click="handleDisconnect(provider.id)"
                            >
                                <span v-if="oauth.loading.value && oauth.activeProvider.value === provider.id">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </span>
                                <span v-else>Disconnect</span>
                            </button>

                            <!-- Connect button (popup or redirect) -->
                            <button
                                v-else
                                class="btn btn-sm btn-primary"
                                :disabled="oauth.loading.value"
                                :style="{ backgroundColor: provider.color, borderColor: provider.color }"
                                @click="handleConnect(provider.id)"
                            >
                                <span v-if="oauth.loading.value && oauth.activeProvider.value === provider.id">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </span>
                                <span v-else>
                                    <i :class="provider.icon" class="mr-1"></i> Connect
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useOAuth } from '../composables/useOAuth'
import type { OAuthConnection } from '../interfaces'

interface Props {
    userConnections?: Record<string, OAuthConnection>
    oauthBaseUrl?: string
    /** OAuth flow mode: 'popup' or 'redirect' */
    mode?: 'popup' | 'redirect'
}

const props = withDefaults(defineProps<Props>(), {
    userConnections: () => ({}),
    oauthBaseUrl: '/api/oauth',
    mode: 'popup',
})

const emit = defineEmits<{
    (e: 'connection-updated', payload: { provider: string; connected: boolean }): void
}>()

const oauth = useOAuth({
    apiBaseUrl: props.oauthBaseUrl,
    mode: props.mode,
})

// Initialize connections from props
onMounted(() => {
    if (props.userConnections && Object.keys(props.userConnections).length > 0) {
        oauth.setConnections(props.userConnections)
    } else {
        // Fetch from backend if not provided
        oauth.fetchConnections()
    }
})

/**
 * Handle connecting a provider via popup or redirect.
 */
async function handleConnect(providerId: string) {
    try {
        await oauth.linkProvider(providerId)
        emit('connection-updated', { provider: providerId, connected: true })
    } catch {
        // Error is already set in oauth.error
    }
}

/**
 * Handle disconnecting a provider.
 */
async function handleDisconnect(providerId: string) {
    if (!confirm('Are you sure you want to disconnect this provider?')) {
        return
    }

    const success = await oauth.disconnectProvider(providerId)
    if (success) {
        emit('connection-updated', { provider: providerId, connected: false })
    }
}
</script>

<style scoped>
.oauth-connections-component .list-group-item {
    transition: background-color 0.2s ease;
}

.oauth-connections-component .list-group-item:hover {
    background-color: #f8f9fa;
}

.oauth-connections-component .btn {
    transition: all 0.3s ease;
}

.oauth-connections-component .btn:hover {
    transform: translateY(-1px);
}
</style>

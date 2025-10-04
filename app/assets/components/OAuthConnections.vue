<template>
    <div class="oauth-connections-component">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">OAuth Connections</h3>
            </div>
            <div class="card-body">
                <p class="text-muted">Connect or disconnect OAuth providers for your account</p>

                <div class="list-group">
                    <div
                        v-for="provider in providers"
                        :key="provider.id"
                        class="list-group-item d-flex justify-content-between align-items-center"
                    >
                        <div>
                            <i :class="[provider.icon, 'fa-lg', 'mr-2']"></i>
                            <strong>{{ provider.name }}</strong>
                        </div>
                        <div>
                            <span v-if="isConnected(provider.id)" class="badge badge-success">
                                Connected
                            </span>
                            <button
                                v-if="isConnected(provider.id)"
                                class="btn btn-sm btn-danger ml-2"
                                @click="disconnectProvider(provider.id)"
                            >
                                Disconnect
                            </button>
                            <a
                                v-else
                                :href="getLinkUrl(provider.id)"
                                class="btn btn-sm btn-primary"
                            >
                                Connect
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

interface Provider {
    id: string
    name: string
    icon: string
}

interface Props {
    userConnections?: Record<string, any>
    oauthBaseUrl?: string
}

const props = withDefaults(defineProps<Props>(), {
    userConnections: () => ({}),
    oauthBaseUrl: '/oauth'
})

const emit = defineEmits<{
    (e: 'connection-updated', payload: { provider: string; connected: boolean }): void
}>()

const providers: Provider[] = [
    { id: 'google', name: 'Google', icon: 'fab fa-google' },
    { id: 'facebook', name: 'Facebook', icon: 'fab fa-facebook' },
    { id: 'linkedin', name: 'LinkedIn', icon: 'fab fa-linkedin' },
    { id: 'microsoft', name: 'Microsoft', icon: 'fab fa-microsoft' }
]

const connections = ref({ ...props.userConnections })

const isConnected = (providerId: string) => {
    return !!connections.value[providerId]
}

const getLinkUrl = (providerId: string) => {
    return `${props.oauthBaseUrl}/link/${providerId}`
}

const disconnectProvider = async (providerId: string) => {
    if (!confirm('Are you sure you want to disconnect this provider?')) {
        return
    }

    try {
        const response = await fetch(`${props.oauthBaseUrl}/disconnect/${providerId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        })

        const data = await response.json()

        if (data.success) {
            delete connections.value[providerId]
            emit('connection-updated', { provider: providerId, connected: false })
        } else {
            alert(data.message || 'Failed to disconnect provider')
        }
    } catch (error) {
        alert('An error occurred while disconnecting')
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

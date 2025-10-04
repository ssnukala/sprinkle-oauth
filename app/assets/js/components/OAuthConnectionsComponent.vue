<template>
  <div class="oauth-connections-component">
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">{{ $t('OAUTH.CONNECTIONS.TITLE') }}</h3>
      </div>
      <div class="card-body">
        <p class="text-muted">{{ $t('OAUTH.CONNECTIONS.DESCRIPTION') }}</p>

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
                {{ $t('OAUTH.CONNECTIONS.CONNECTED') }}
              </span>
              <button
                v-if="isConnected(provider.id)"
                class="btn btn-sm btn-danger ml-2"
                @click="disconnectProvider(provider.id)"
              >
                {{ $t('OAUTH.CONNECTIONS.DISCONNECT') }}
              </button>
              <a
                v-else
                :href="getLinkUrl(provider.id)"
                class="btn btn-sm btn-primary"
              >
                {{ $t('OAUTH.CONNECTIONS.CONNECT') }}
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'OAuthConnectionsComponent',

  props: {
    userConnections: {
      type: Object,
      default: () => ({})
    },
    oauthBaseUrl: {
      type: String,
      default: '/oauth'
    }
  },

  data() {
    return {
      providers: [
        { id: 'google', name: 'Google', icon: 'fab fa-google' },
        { id: 'facebook', name: 'Facebook', icon: 'fab fa-facebook' },
        { id: 'linkedin', name: 'LinkedIn', icon: 'fab fa-linkedin' },
        { id: 'microsoft', name: 'Microsoft', icon: 'fab fa-microsoft' }
      ],
      connections: { ...this.userConnections }
    };
  },

  methods: {
    isConnected(providerId) {
      return !!this.connections[providerId];
    },

    getLinkUrl(providerId) {
      return `${this.oauthBaseUrl}/link/${providerId}`;
    },

    async disconnectProvider(providerId) {
      if (!confirm(this.$t('OAUTH.CONNECTIONS.DISCONNECT_CONFIRM'))) {
        return;
      }

      try {
        const response = await fetch(`${this.oauthBaseUrl}/disconnect/${providerId}`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          }
        });

        const data = await response.json();

        if (data.success) {
          this.$delete(this.connections, providerId);
          this.$emit('connection-updated', { provider: providerId, connected: false });
        } else {
          alert(data.message || this.$t('OAUTH.CONNECTIONS.ERROR'));
        }
      } catch (error) {
        alert(this.$t('OAUTH.CONNECTIONS.ERROR'));
      }
    }
  }
};
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

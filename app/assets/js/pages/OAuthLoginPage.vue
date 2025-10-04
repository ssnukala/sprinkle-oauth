<template>
  <div class="oauth-login-page">
    <div class="login-box">
      <div class="login-logo">
        <a :href="siteUrl"><b>{{ siteTitle }}</b></a>
      </div>

      <div class="card">
        <div class="card-body login-card-body">
          <p class="login-box-msg">{{ $t('OAUTH.LOGIN.SUBTITLE') }}</p>

          <!-- Alerts Component -->
          <div v-if="alerts.length" class="alerts-container">
            <div
              v-for="(alert, index) in alerts"
              :key="index"
              :class="['alert', `alert-${alert.type}`]"
              role="alert"
            >
              {{ alert.message }}
            </div>
          </div>

          <!-- Standard Login Form -->
          <form @submit.prevent="handleLogin">
            <div class="input-group mb-3">
              <input
                v-model="username"
                type="text"
                name="username"
                class="form-control"
                :placeholder="$t('USERNAME')"
                required
              />
              <div class="input-group-append">
                <div class="input-group-text">
                  <span class="fas fa-user"></span>
                </div>
              </div>
            </div>

            <div class="input-group mb-3">
              <input
                v-model="password"
                type="password"
                name="password"
                class="form-control"
                :placeholder="$t('PASSWORD')"
                required
              />
              <div class="input-group-append">
                <div class="input-group-text">
                  <span class="fas fa-lock"></span>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-8">
                <div class="icheck-primary">
                  <input v-model="remember" type="checkbox" id="remember" name="remember" />
                  <label for="remember">
                    {{ $t('REMEMBER_ME') }}
                  </label>
                </div>
              </div>
              <div class="col-4">
                <button type="submit" class="btn btn-primary btn-block">
                  {{ $t('SIGN_IN') }}
                </button>
              </div>
            </div>
          </form>

          <!-- OAuth Login Options -->
          <div v-if="enabledProviders.length" class="social-auth-links text-center mb-3 mt-3">
            <p>- {{ $t('OAUTH.LOGIN.OR') }} -</p>

            <a
              v-if="enabledProviders.includes('google')"
              :href="getOAuthUrl('google')"
              class="btn btn-block btn-danger mb-2"
            >
              <i class="fab fa-google mr-2"></i> {{ $t('OAUTH.LOGIN.GOOGLE') }}
            </a>

            <a
              v-if="enabledProviders.includes('facebook')"
              :href="getOAuthUrl('facebook')"
              class="btn btn-block btn-primary mb-2"
            >
              <i class="fab fa-facebook mr-2"></i> {{ $t('OAUTH.LOGIN.FACEBOOK') }}
            </a>

            <a
              v-if="enabledProviders.includes('linkedin')"
              :href="getOAuthUrl('linkedin')"
              class="btn btn-block btn-info mb-2"
            >
              <i class="fab fa-linkedin mr-2"></i> {{ $t('OAUTH.LOGIN.LINKEDIN') }}
            </a>

            <a
              v-if="enabledProviders.includes('microsoft')"
              :href="getOAuthUrl('microsoft')"
              class="btn btn-block btn-secondary mb-2"
            >
              <i class="fab fa-microsoft mr-2"></i> {{ $t('OAUTH.LOGIN.MICROSOFT') }}
            </a>
          </div>

          <!-- Additional Links -->
          <p class="mb-1">
            <a :href="forgotPasswordUrl">{{ $t('FORGOT_PASSWORD') }}</a>
          </p>
          <p class="mb-0">
            <a :href="registerUrl" class="text-center">{{ $t('REGISTER_NEW_ACCOUNT') }}</a>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'OAuthLoginPage',

  props: {
    siteTitle: {
      type: String,
      default: 'UserFrosting'
    },
    siteUrl: {
      type: String,
      default: '/'
    },
    loginUrl: {
      type: String,
      default: '/account/login'
    },
    forgotPasswordUrl: {
      type: String,
      default: '/account/forgot-password'
    },
    registerUrl: {
      type: String,
      default: '/account/register'
    },
    enabledProviders: {
      type: Array,
      default: () => []
    },
    oauthBaseUrl: {
      type: String,
      default: '/oauth'
    }
  },

  data() {
    return {
      username: '',
      password: '',
      remember: false,
      alerts: []
    };
  },

  methods: {
    async handleLogin() {
      try {
        const response = await fetch(this.loginUrl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            username: this.username,
            password: this.password,
            remember: this.remember
          })
        });

        if (response.ok) {
          window.location.href = '/';
        } else {
          const data = await response.json();
          this.alerts.push({
            type: 'danger',
            message: data.message || 'Login failed'
          });
        }
      } catch (error) {
        this.alerts.push({
          type: 'danger',
          message: 'An error occurred during login'
        });
      }
    },

    getOAuthUrl(provider) {
      return `${this.oauthBaseUrl}/${provider}`;
    }
  }
};
</script>

<style scoped>
.oauth-login-page {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  background: #f4f6f9;
}

.login-box {
  width: 360px;
}

.social-auth-links .btn {
  transition: all 0.3s ease;
}

.social-auth-links .btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.alerts-container {
  margin-bottom: 1rem;
}
</style>

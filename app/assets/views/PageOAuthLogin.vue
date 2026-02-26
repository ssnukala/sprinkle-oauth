<!--
  OAuth Sprinkle (https://www.userfrosting.com)

  @link      https://github.com/ssnukala/sprinkle-oauth
  @copyright Copyright (c) 2026 Srinivas Nukala
  @license   https://github.com/ssnukala/sprinkle-oauth/blob/master/LICENSE.md (MIT License)
-->

<!-- PageOAuthLogin.vue -->
<!-- Login page with standard username/password form and OAuth provider buttons.
     OAuth buttons use popup mode for seamless login without leaving the page. -->
<template>
    <div class="oauth-login-page">
        <div class="login-box">
            <div class="login-logo">
                <a :href="siteUrl"><b>{{ siteTitle }}</b></a>
            </div>

            <div class="card">
                <div class="card-body login-card-body">
                    <p class="login-box-msg">Sign in to your account</p>

                    <!-- Alerts -->
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
                                class="form-control"
                                placeholder="Username"
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
                                class="form-control"
                                placeholder="Password"
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
                                    <input v-model="remember" type="checkbox" id="remember" />
                                    <label for="remember">Remember Me</label>
                                </div>
                            </div>
                            <div class="col-4">
                                <button type="submit" class="btn btn-primary btn-block">
                                    Sign In
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- OAuth Login Options -->
                    <div v-if="enabledProviders.length" class="social-auth-links text-center mb-3 mt-3">
                        <p>- OR -</p>

                        <button
                            v-for="provider in visibleProviders"
                            :key="provider.id"
                            class="btn btn-block mb-2"
                            :class="providerButtonClass(provider.id)"
                            :disabled="oauth.loading.value"
                            @click="handleOAuthLogin(provider.id)"
                        >
                            <span v-if="oauth.loading.value && oauth.activeProvider.value === provider.id">
                                <i class="fas fa-spinner fa-spin mr-2"></i> Connecting...
                            </span>
                            <span v-else>
                                <i :class="provider.icon" class="mr-2"></i> Sign in with {{ provider.name }}
                            </span>
                        </button>
                    </div>

                    <!-- Additional Links -->
                    <p class="mb-1">
                        <a :href="forgotPasswordUrl">Forgot Password?</a>
                    </p>
                    <p class="mb-0">
                        <a :href="registerUrl" class="text-center">Register a new account</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useOAuth } from '../composables/useOAuth'

interface Props {
    siteTitle?: string
    siteUrl?: string
    loginUrl?: string
    forgotPasswordUrl?: string
    registerUrl?: string
    enabledProviders?: string[]
    oauthBaseUrl?: string
    /** OAuth flow mode: 'popup' or 'redirect' */
    oauthMode?: 'popup' | 'redirect'
}

const props = withDefaults(defineProps<Props>(), {
    siteTitle: 'UserFrosting',
    siteUrl: '/',
    loginUrl: '/account/login',
    forgotPasswordUrl: '/account/forgot-password',
    registerUrl: '/account/register',
    enabledProviders: () => [],
    oauthBaseUrl: '/api/oauth',
    oauthMode: 'popup',
})

const username = ref('')
const password = ref('')
const remember = ref(false)
const alerts = ref<Array<{ type: string; message: string }>>([])

const oauth = useOAuth({
    apiBaseUrl: props.oauthBaseUrl,
    mode: props.oauthMode,
})

/**
 * Filter provider definitions to only show enabled ones.
 */
const visibleProviders = computed(() =>
    oauth.providers.value.filter(p => props.enabledProviders.includes(p.id))
)

/**
 * Get Bootstrap button class for a provider.
 */
function providerButtonClass(providerId: string): string {
    const classes: Record<string, string> = {
        google: 'btn-danger',
        facebook: 'btn-primary',
        linkedin: 'btn-info',
        microsoft: 'btn-secondary',
    }
    return classes[providerId] || 'btn-default'
}

/**
 * Handle standard login form submission.
 */
async function handleLogin() {
    try {
        const response = await fetch(props.loginUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                username: username.value,
                password: password.value,
                remember: remember.value,
            }),
        })

        if (response.ok) {
            window.location.href = '/'
        } else {
            const data = await response.json()
            alerts.value.push({
                type: 'danger',
                message: data.message || 'Login failed',
            })
        }
    } catch {
        alerts.value.push({
            type: 'danger',
            message: 'An error occurred during login',
        })
    }
}

/**
 * Handle OAuth login via popup or redirect.
 */
async function handleOAuthLogin(provider: string) {
    alerts.value = [] // Clear previous alerts

    try {
        const result = await oauth.login(provider)

        if (result?.success) {
            // Redirect to dashboard or the URL returned by the backend
            window.location.href = result.redirect || '/'
        }
    } catch (err: any) {
        // Don't show error if user just closed the popup
        if (err.message !== 'OAuth popup was closed') {
            alerts.value.push({
                type: 'danger',
                message: err.message || 'OAuth login failed',
            })
        }
    }
}
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

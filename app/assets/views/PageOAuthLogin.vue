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

                        <a
                            v-if="enabledProviders.includes('google')"
                            :href="getOAuthUrl('google')"
                            class="btn btn-block btn-danger mb-2"
                        >
                            <i class="fab fa-google mr-2"></i> Sign in with Google
                        </a>

                        <a
                            v-if="enabledProviders.includes('facebook')"
                            :href="getOAuthUrl('facebook')"
                            class="btn btn-block btn-primary mb-2"
                        >
                            <i class="fab fa-facebook mr-2"></i> Sign in with Facebook
                        </a>

                        <a
                            v-if="enabledProviders.includes('linkedin')"
                            :href="getOAuthUrl('linkedin')"
                            class="btn btn-block btn-info mb-2"
                        >
                            <i class="fab fa-linkedin mr-2"></i> Sign in with LinkedIn
                        </a>

                        <a
                            v-if="enabledProviders.includes('microsoft')"
                            :href="getOAuthUrl('microsoft')"
                            class="btn btn-block btn-secondary mb-2"
                        >
                            <i class="fab fa-microsoft mr-2"></i> Sign in with Microsoft
                        </a>
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
import { ref } from 'vue'

interface Props {
    siteTitle?: string
    siteUrl?: string
    loginUrl?: string
    forgotPasswordUrl?: string
    registerUrl?: string
    enabledProviders?: string[]
    oauthBaseUrl?: string
}

const props = withDefaults(defineProps<Props>(), {
    siteTitle: 'UserFrosting',
    siteUrl: '/',
    loginUrl: '/account/login',
    forgotPasswordUrl: '/account/forgot-password',
    registerUrl: '/account/register',
    enabledProviders: () => [],
    oauthBaseUrl: '/oauth'
})

const username = ref('')
const password = ref('')
const remember = ref(false)
const alerts = ref<Array<{ type: string; message: string }>>([])

const handleLogin = async () => {
    try {
        const response = await fetch(props.loginUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                username: username.value,
                password: password.value,
                remember: remember.value
            })
        })

        if (response.ok) {
            window.location.href = '/'
        } else {
            const data = await response.json()
            alerts.value.push({
                type: 'danger',
                message: data.message || 'Login failed'
            })
        }
    } catch (error) {
        alerts.value.push({
            type: 'danger',
            message: 'An error occurred during login'
        })
    }
}

const getOAuthUrl = (provider: string) => {
    return `${props.oauthBaseUrl}/${provider}`
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

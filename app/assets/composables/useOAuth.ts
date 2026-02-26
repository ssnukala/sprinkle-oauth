/*
 * OAuth Sprinkle (https://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-oauth
 * @copyright Copyright (c) 2026 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-oauth/blob/master/LICENSE.md (MIT License)
 */

import { ref, computed } from 'vue'
import type { OAuthProvider, OAuthConnection, OAuthResult, OAuthConfig } from '../interfaces'

/**
 * Default supported OAuth providers with display metadata.
 */
const DEFAULT_PROVIDERS: OAuthProvider[] = [
    { id: 'google', name: 'Google', icon: 'fab fa-google', color: '#db4437' },
    { id: 'facebook', name: 'Facebook', icon: 'fab fa-facebook', color: '#4267b2' },
    { id: 'linkedin', name: 'LinkedIn', icon: 'fab fa-linkedin', color: '#0077b5' },
    { id: 'microsoft', name: 'Microsoft', icon: 'fab fa-microsoft', color: '#00a4ef' },
]

/**
 * Vue composable for OAuth authentication flows.
 *
 * Supports popup-based and redirect-based OAuth for Google, Facebook, LinkedIn, Microsoft.
 * The popup flow opens a new window for the OAuth consent, listens for the result via
 * postMessage or polling, and resolves without leaving the current page.
 *
 * @param config - Configuration options
 */
export function useOAuth(config: OAuthConfig = {}) {
    const {
        apiBaseUrl = '/api/oauth',
        mode = 'popup',
        popupWidth = 500,
        popupHeight = 600,
        redirectUrl = '/dashboard',
    } = config

    const loading = ref(false)
    const activeProvider = ref<string | null>(null)
    const error = ref<string | null>(null)
    const connections = ref<Record<string, OAuthConnection>>({})

    /**
     * Available provider definitions.
     */
    const providers = computed(() => DEFAULT_PROVIDERS)

    /**
     * Check if a provider is currently connected.
     */
    function isConnected(providerId: string): boolean {
        return !!connections.value[providerId]
    }

    /**
     * Set the current connections from backend data.
     */
    function setConnections(data: Record<string, OAuthConnection>) {
        connections.value = { ...data }
    }

    /**
     * Start OAuth login flow for a provider.
     *
     * In popup mode: opens a centered popup window to the OAuth redirect URL.
     * The popup navigates through the provider's consent screen, then the backend
     * callback renders a small HTML page that posts the result back via window.opener.
     *
     * In redirect mode: navigates the current window to the OAuth URL.
     */
    async function login(provider: string): Promise<OAuthResult | void> {
        error.value = null
        activeProvider.value = provider

        const url = `${apiBaseUrl}/${provider}`

        if (mode === 'redirect') {
            window.location.href = url
            return
        }

        // Popup mode
        loading.value = true

        try {
            return await openOAuthPopup(url, provider, 'login')
        } catch (err: any) {
            error.value = err.message || 'OAuth login failed'
            throw err
        } finally {
            loading.value = false
            activeProvider.value = null
        }
    }

    /**
     * Start OAuth link flow to connect a provider to the current user.
     */
    async function linkProvider(provider: string): Promise<OAuthResult | void> {
        error.value = null
        activeProvider.value = provider

        const url = `${apiBaseUrl}/link/${provider}`

        if (mode === 'redirect') {
            window.location.href = url
            return
        }

        loading.value = true

        try {
            const result = await openOAuthPopup(url, provider, 'link')
            if (result?.success) {
                // Refresh connections after linking
                await fetchConnections()
            }
            return result
        } catch (err: any) {
            error.value = err.message || 'OAuth link failed'
            throw err
        } finally {
            loading.value = false
            activeProvider.value = null
        }
    }

    /**
     * Disconnect an OAuth provider from the current user.
     */
    async function disconnectProvider(provider: string): Promise<boolean> {
        error.value = null
        loading.value = true

        try {
            const response = await fetch(`${apiBaseUrl}/disconnect/${provider}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
            })

            const data = await response.json()

            if (data.success) {
                delete connections.value[provider]
                return true
            } else {
                error.value = data.message || 'Failed to disconnect provider'
                return false
            }
        } catch (err: any) {
            error.value = err.message || 'An error occurred while disconnecting'
            return false
        } finally {
            loading.value = false
        }
    }

    /**
     * Fetch current user's OAuth connections from the backend.
     */
    async function fetchConnections(): Promise<void> {
        try {
            const response = await fetch(`${apiBaseUrl}/connections`, {
                headers: { 'Accept': 'application/json' },
            })

            if (response.ok) {
                const data = await response.json()
                connections.value = data.connections || {}
            }
        } catch {
            // Silently fail — connections will appear empty
        }
    }

    /**
     * Open an OAuth popup window and wait for the result.
     *
     * The backend callback page (rendered by OAuthController::callbackPopup) posts
     * the authentication result back to the opener via window.postMessage.
     * We also poll the popup to detect if the user closed it manually.
     */
    function openOAuthPopup(url: string, provider: string, action: string): Promise<OAuthResult> {
        return new Promise((resolve, reject) => {
            // Append popup flag so backend knows to render postMessage page
            const popupUrl = url + (url.includes('?') ? '&' : '?') + 'popup=1'

            // Center the popup on screen
            const left = Math.max(0, (window.screen.width - popupWidth) / 2 + (window.screenX || window.screenLeft || 0))
            const top = Math.max(0, (window.screen.height - popupHeight) / 2 + (window.screenY || window.screenTop || 0))
            const features = `width=${popupWidth},height=${popupHeight},left=${left},top=${top},toolbar=no,menubar=no,scrollbars=yes,resizable=yes`

            const popup = window.open(popupUrl, `oauth_${provider}`, features)

            if (!popup || popup.closed) {
                reject(new Error('Popup was blocked. Please allow popups for this site.'))
                return
            }

            // Listen for postMessage from the popup
            function onMessage(event: MessageEvent) {
                // Validate origin — accept same-origin messages
                if (event.origin !== window.location.origin) return

                const data = event.data
                if (data?.type !== 'oauth_result' || data?.provider !== provider) return

                cleanup()

                if (data.success) {
                    resolve({
                        success: true,
                        provider,
                        action: data.action || action,
                        message: data.message,
                        user: data.user,
                        isNewUser: data.isNewUser,
                        redirect: data.redirect,
                    } as OAuthResult)
                } else {
                    reject(new Error(data.message || 'OAuth authentication failed'))
                }
            }

            window.addEventListener('message', onMessage)

            // Poll to detect if popup was closed without completing OAuth
            const pollTimer = setInterval(() => {
                if (popup.closed) {
                    cleanup()
                    reject(new Error('OAuth popup was closed'))
                }
            }, 500)

            function cleanup() {
                window.removeEventListener('message', onMessage)
                clearInterval(pollTimer)
                if (popup && !popup.closed) {
                    popup.close()
                }
            }
        })
    }

    /**
     * Get the direct OAuth URL for a provider (for use in <a> tags or manual redirects).
     */
    function getOAuthUrl(provider: string): string {
        return `${apiBaseUrl}/${provider}`
    }

    /**
     * Get the link URL for connecting a provider to the current user.
     */
    function getLinkUrl(provider: string): string {
        return `${apiBaseUrl}/link/${provider}`
    }

    return {
        // State
        loading,
        activeProvider,
        error,
        connections,
        providers,

        // Methods
        login,
        linkProvider,
        disconnectProvider,
        fetchConnections,
        setConnections,
        isConnected,
        getOAuthUrl,
        getLinkUrl,
    }
}

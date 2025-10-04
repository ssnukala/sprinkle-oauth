/**
 * OAuth Sprinkle Vue Router Routes
 * 
 * Frontend routes for OAuth authentication pages
 */
export default [
    {
        path: '/oauth/login',
        name: 'oauth.login',
        meta: {
            guest: {
                redirect: { name: 'home' }
            },
            title: 'OAUTH.LOGIN.TITLE',
            description: 'OAUTH.LOGIN.SUBTITLE'
        },
        component: () => import('../views/PageOAuthLogin.vue')
    }
]

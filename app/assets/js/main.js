import { createApp } from 'vue';
import OAuthLoginPage from './pages/OAuthLoginPage.vue';
import OAuthConnectionsComponent from './components/OAuthConnectionsComponent.vue';

// Create Vue app for OAuth Login Page
if (document.getElementById('oauth-login-app')) {
  const loginApp = createApp(OAuthLoginPage, {
    siteTitle: window.appConfig?.siteTitle || 'UserFrosting',
    siteUrl: window.appConfig?.siteUrl || '/',
    loginUrl: window.appConfig?.loginUrl || '/account/login',
    forgotPasswordUrl: window.appConfig?.forgotPasswordUrl || '/account/forgot-password',
    registerUrl: window.appConfig?.registerUrl || '/account/register',
    enabledProviders: window.appConfig?.enabledProviders || [],
    oauthBaseUrl: window.appConfig?.oauthBaseUrl || '/oauth'
  });

  loginApp.mount('#oauth-login-app');
}

// Create Vue component for OAuth Connections
if (document.getElementById('oauth-connections-component')) {
  const connectionsApp = createApp(OAuthConnectionsComponent, {
    userConnections: window.appConfig?.userConnections || {},
    oauthBaseUrl: window.appConfig?.oauthBaseUrl || '/oauth'
  });

  connectionsApp.mount('#oauth-connections-component');
}

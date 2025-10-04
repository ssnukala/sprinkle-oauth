# OAuth Sprinkle - Frontend Assets

This directory contains Vue.js components and frontend assets for the OAuth Sprinkle.

## Structure

```
app/assets/
├── js/
│   ├── components/          # Reusable Vue components
│   │   └── OAuthConnectionsComponent.vue
│   ├── pages/               # Full page Vue components
│   │   └── OAuthLoginPage.vue
│   └── main.js              # Entry point
├── css/                     # Custom stylesheets
├── package.json             # Node dependencies
├── vite.config.js           # Vite build configuration
└── README.md                # This file
```

## Components

### OAuthLoginPage.vue
Full-page Vue component for OAuth login page with traditional username/password login and OAuth provider buttons.

**Props:**
- `siteTitle` - Site title (default: 'UserFrosting')
- `siteUrl` - Site URL (default: '/')
- `loginUrl` - Login endpoint (default: '/account/login')
- `forgotPasswordUrl` - Forgot password URL
- `registerUrl` - Registration URL
- `enabledProviders` - Array of enabled OAuth providers
- `oauthBaseUrl` - OAuth base URL (default: '/oauth')

### OAuthConnectionsComponent.vue
Component for managing OAuth connections in user settings/profile page.

**Props:**
- `userConnections` - Object with user's current connections
- `oauthBaseUrl` - OAuth base URL (default: '/oauth')

**Events:**
- `connection-updated` - Emitted when connection status changes

## Development

### Prerequisites
- Node.js >= 18.0.0
- npm or yarn

### Setup

```bash
cd app/assets
npm install
```

### Development Server

```bash
npm run dev
```

This starts a Vite development server at http://localhost:3000

### Build for Production

```bash
npm run build
```

Builds optimized assets to `../public/assets/oauth/`

## Integration with UserFrosting

### Using the Login Page

Replace the Twig template with a simple wrapper that loads the Vue app:

```twig
{# templates/pages/oauth-login-vue.html.twig #}
{% extends "pages/abstract/base.html.twig" %}

{% block body %}
<div id="oauth-login-app"></div>

<script>
window.appConfig = {
    siteTitle: '{{ site.title }}',
    siteUrl: '{{ site.uri.public }}',
    loginUrl: '{{ urlFor('login') }}',
    forgotPasswordUrl: '{{ urlFor('forgot-password') }}',
    registerUrl: '{{ urlFor('register') }}',
    enabledProviders: {{ enabledProviders|json_encode|raw }},
    oauthBaseUrl: '/oauth'
};
</script>
<script type="module" src="{{ asset('assets/oauth/js/main.js') }}"></script>
{% endblock %}
```

### Using the Connections Component

```twig
{# In user profile/settings page #}
<div id="oauth-connections-component"></div>

<script>
window.appConfig = window.appConfig || {};
window.appConfig.userConnections = {{ userConnections|json_encode|raw }};
window.appConfig.oauthBaseUrl = '/oauth';
</script>
```

## Browser Support

- Chrome/Edge (latest)
- Firefox (latest)
- Safari (latest)
- Modern browsers with ES6+ support

## Notes

- Components use Vue 3 Composition API
- Styling uses Bootstrap 4/5 classes (adjust as needed)
- Font Awesome icons required for OAuth provider icons
- Translations use i18n (integrate with UserFrosting's translation system)

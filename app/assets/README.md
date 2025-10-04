# OAuth Sprinkle - Frontend Assets

This directory contains Vue.js components and frontend assets for the OAuth Sprinkle, following the UserFrosting 6 Admin Sprinkle pattern.

## Structure

Following the UserFrosting Admin sprinkle structure:

```
app/assets/
├── components/          # Reusable Vue components
│   ├── OAuthConnections.vue
│   └── index.ts
├── composables/         # Vue Composition API composables
│   └── index.ts
├── interfaces/          # TypeScript interfaces
│   └── index.ts
├── routes/              # Route definitions
│   └── index.ts
├── views/               # Page components
│   ├── PageOAuthLogin.vue
│   └── index.ts
└── index.ts             # Main entry point (plugin)
```

## Components

### Views

**PageOAuthLogin.vue**
Full-page Vue component for OAuth login page with:
- Traditional username/password login form
- OAuth provider buttons (Google, Facebook, LinkedIn, Microsoft)
- Responsive design
- TypeScript support

**Props:**
```typescript
{
    siteTitle?: string          // Site title (default: 'UserFrosting')
    siteUrl?: string            // Site URL (default: '/')
    loginUrl?: string           // Login endpoint
    forgotPasswordUrl?: string  // Forgot password URL
    registerUrl?: string        // Registration URL
    enabledProviders?: string[] // Array of enabled providers
    oauthBaseUrl?: string       // OAuth base URL (default: '/oauth')
}
```

### Components

**OAuthConnections.vue**
Component for managing OAuth connections in user settings/profile page.

**Props:**
```typescript
{
    userConnections?: Record<string, any>  // Current connections
    oauthBaseUrl?: string                  // OAuth base URL
}
```

**Events:**
- `connection-updated` - Emitted when connection status changes

## TypeScript

The sprinkle uses TypeScript for type safety and better developer experience, consistent with UserFrosting 6 Admin Sprinkle.

## Package Exports

The package exports multiple entry points:

```typescript
import OAuthSprinkle from '@userfrosting/sprinkle-oauth'
import { OAuthLoginView } from '@userfrosting/sprinkle-oauth/views'
import { OAuthConnections } from '@userfrosting/sprinkle-oauth/components'
```

## Development

### Prerequisites
- Node.js >= 18.0.0
- TypeScript 5.x
- Vue 3.x

### Setup

```bash
npm install
```

### Build

```bash
npm run build
```

## Integration with UserFrosting

### As a Plugin

The OAuth sprinkle can be imported and used as a Vue plugin:

```typescript
import { createApp } from 'vue'
import OAuthSprinkle from '@userfrosting/sprinkle-oauth'

const app = createApp(App)
app.use(OAuthSprinkle)
```

### Using Individual Components

```vue
<template>
    <OAuthLoginView
        :siteTitle="config.siteTitle"
        :enabledProviders="config.providers"
    />
</template>

<script setup lang="ts">
import { OAuthLoginView } from '@userfrosting/sprinkle-oauth/views'

const config = {
    siteTitle: 'My App',
    providers: ['google', 'facebook']
}
</script>
```

### Using OAuth Connections Component

```vue
<template>
    <OAuthConnections
        :userConnections="userConnections"
        @connection-updated="handleUpdate"
    />
</template>

<script setup lang="ts">
import { OAuthConnections } from '@userfrosting/sprinkle-oauth/components'

const userConnections = {
    google: { /* connection data */ },
    facebook: { /* connection data */ }
}

const handleUpdate = (event: { provider: string; connected: boolean }) => {
    console.log('Connection updated:', event)
}
</script>
```

## Architecture

This structure follows the UserFrosting 6 Admin Sprinkle pattern:
- **TypeScript** for type safety
- **Modular exports** via package.json exports field
- **Vue 3 Composition API** with `<script setup>`
- **Proper separation** of views, components, composables, and interfaces

## Browser Support

- Chrome/Edge (latest)
- Firefox (latest)
- Safari (latest)
- Modern browsers with ES2020+ support

## Notes

- Uses Vue 3 Composition API with TypeScript
- Follows UserFrosting 6 Admin Sprinkle architecture
- Peer dependencies: Vue 3, Vue Router, UserFrosting core sprinkles
- Built with Vite for optimal performance

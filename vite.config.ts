import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// https://vitejs.dev/config/
export default defineConfig({
    plugins: [vue()],
    build: {
        lib: {
            entry: 'app/assets/index.ts',
            name: 'OAuthSprinkle',
            formats: ['es']
        },
        rollupOptions: {
            external: ['vue', 'vue-router'],
            output: {
                globals: {
                    vue: 'Vue',
                    'vue-router': 'VueRouter'
                }
            }
        }
    }
})

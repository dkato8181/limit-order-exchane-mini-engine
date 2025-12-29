import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'
import tailwindcss from '@tailwindcss/vite'

// https://vite.dev/config/
export default defineConfig({
  plugins: [vue(),tailwindcss()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
  server: {
    // make Vite listen on all addresses (useful for containers/VMs) and ensure HMR websocket info is explicit
    host: true,
    port: 5173,
    hmr: {
      protocol: 'ws', // or 'wss' if using HTTPS
      host: 'localhost', // set to the host browsers should connect to
      port: 5173,
    },
  },
})

import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// https://vite.dev/config/
export default defineConfig({
  plugins: [react()],
  server: {
    proxy: {
      '/api': {
        target: 'https://7fwhq6pkexd2p6px4q7fstuvd40hsjaq.lambda-url.us-east-2.on.aws/',
        changeOrigin: true,
        rewrite: (path) => path.replace(/^\/api/, ''),
      },
      '/save': {
        target: 'http://localhost:3000',
        changeOrigin: true,
      },
      '/posts': {
        target: 'http://localhost:3000',
        changeOrigin: true,
      },
      '/auth': {
        target: 'http://localhost:3000',
        changeOrigin: true,
      },
      '/local-draft': {
        target: 'http://localhost:3000',
        changeOrigin: true,
      },
    }
  }
})

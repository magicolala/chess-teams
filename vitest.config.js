import { defineConfig } from 'vitest/config'
import { fileURLToPath } from 'node:url'
import { dirname, resolve } from 'node:path'

const __filename = fileURLToPath(import.meta.url)
const __dirname = dirname(__filename)

export default defineConfig({
  test: {
    environment: 'jsdom',
    setupFiles: ['./assets/tests/setup.js'],
  },
  resolve: {
    alias: {
      '@magicolala/neo-chess-board': resolve(__dirname, 'assets/tests/stubs/neo-chess-board.js'),
    },
  },
})

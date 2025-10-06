import { vi, beforeEach } from 'vitest'

if (typeof global.requestAnimationFrame !== 'function') {
  global.requestAnimationFrame = (cb) => cb()
}

if (typeof global.cancelAnimationFrame !== 'function') {
  global.cancelAnimationFrame = () => {}
}

beforeEach(() => {
  vi.restoreAllMocks()
})

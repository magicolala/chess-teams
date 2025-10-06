export class NeoChessBoard {
  constructor(element, options = {}) {
    this.element = element
    this.options = options
    this._position = options.fen || 'startpos'
    this._interactive = !!options.interactive
    this.listeners = new Map()
  }

  on(event, callback) {
    if (!this.listeners.has(event)) {
      this.listeners.set(event, [])
    }
    this.listeners.get(event).push(callback)
  }

  emit(event, payload) {
    (this.listeners.get(event) || []).forEach(cb => cb(payload))
  }

  destroy() {}

  getPosition() {
    return this._position
  }

  setPosition(fen) {
    this._position = fen
  }

  setInteractive(flag) {
    this._interactive = !!flag
  }
}

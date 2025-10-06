import { describe, it, expect, beforeEach, vi } from 'vitest'
import GameBoardController from '../controllers/game-board_controller.js'
import { Chess } from 'chess.js'

function createBareController() {
  return Object.create(GameBoardController.prototype)
}

function createControllerWithPanel() {
  const element = document.createElement('div')
  const panel = document.createElement('div')
  const phase = document.createElement('p')
  const hint = document.createElement('span')

  const brainRole = document.createElement('div')
  brainRole.classList.add('hand-brain-role')
  brainRole.innerHTML = '<span class="role-label"></span><span class="role-value"></span>'

  const handRole = document.createElement('div')
  handRole.classList.add('hand-brain-role')
  handRole.innerHTML = '<span class="role-label"></span><span class="role-value"></span>'

  const button = document.createElement('button')
  button.dataset.piece = 'rook'
  button.classList.add('hand-brain-piece')

  panel.appendChild(phase)
  panel.appendChild(hint)
  panel.appendChild(brainRole)
  panel.appendChild(handRole)
  panel.appendChild(button)

  const controller = createBareController()
  controller.context = { element }
  controller.handBrainPanelTarget = panel
  controller.handBrainPhaseTarget = phase
  controller.handBrainHintTarget = hint
  controller.handBrainRoleBrainTarget = brainRole
  controller.handBrainRoleHandTarget = handRole
  controller.handBrainPieceButtonTargets = [button]
  controller.hasHandBrainPanelTarget = true
  controller.hasHandBrainPhaseTarget = true
  controller.hasHandBrainHintTarget = true
  controller.hasHandBrainRoleBrainTarget = true
  controller.hasHandBrainRoleHandTarget = true
  controller.hasHandBrainPieceButtonTarget = true
  controller.handBrainHintLoading = false
  controller.statusValue = 'live'
  controller.printDebug = vi.fn()
  return { controller, button, brainRole, handRole, phase, hint }
}

describe('GameBoardController hand-brain controls', () => {
  beforeEach(() => {
    document.body.innerHTML = ''
  })

  it('enables piece buttons for the brain and locks them afterwards', () => {
    const { controller, button, brainRole, handRole, phase, hint } = createControllerWithPanel()

    controller.modeValue = 'hand_brain'
    controller.membershipIdValue = 'brain-id'
    controller.handBrainState = {
      currentRole: 'brain',
      pieceHint: null,
      brainMemberId: 'brain-id',
      handMemberId: 'hand-id',
    }

    controller.renderHandBrainPanel()

    expect(button.disabled).toBe(false)
    expect(brainRole.classList.contains('is-you')).toBe(true)
    expect(handRole.classList.contains('is-you')).toBe(false)
    expect(phase.textContent).toContain('Phase cerveau')
    expect(hint.textContent).toBe('—')

    controller.membershipIdValue = 'hand-id'
    controller.handBrainState = {
      currentRole: 'hand',
      pieceHint: 'rook',
      brainMemberId: 'brain-id',
      handMemberId: 'hand-id',
    }

    controller.renderHandBrainPanel()

    expect(button.disabled).toBe(true)
    expect(handRole.classList.contains('is-you')).toBe(true)
    expect(hint.textContent).toContain('tour')
    expect(phase.textContent?.toLowerCase()).toContain('phase main')
  })

  it('rejects moves that do not match the hint in hand-brain mode', () => {
    const controller = createBareController()
    controller.context = { element: document.createElement('div') }
    controller.modeValue = 'hand_brain'
    controller.membershipIdValue = 'hand-id'
    controller.handBrainState = {
      currentRole: 'hand',
      pieceHint: 'rook',
      brainMemberId: 'brain-id',
      handMemberId: 'hand-id',
    }
    controller.printDebug = vi.fn()
    controller.board = {
      setPosition: vi.fn(),
    }
    controller.chessJs = new Chess()

    const resultInvalid = controller.validateHandBrainMove('e2', controller.chessJs.fen(), controller.chessJs.fen())
    expect(resultInvalid).toBe(false)
    expect(controller.board.setPosition).toHaveBeenCalled()

    controller.handBrainState.pieceHint = 'pawn'
    controller.board.setPosition.mockClear()
    const resultValid = controller.validateHandBrainMove('e2', controller.chessJs.fen(), controller.chessJs.fen())
    expect(resultValid).toBe(true)
    expect(controller.board.setPosition).not.toHaveBeenCalled()

    controller.membershipIdValue = 'spectator'
    controller.board.setPosition.mockClear()
    const resultSpectator = controller.validateHandBrainMove('e2', controller.chessJs.fen(), controller.chessJs.fen())
    expect(resultSpectator).toBe(false)
  })
})

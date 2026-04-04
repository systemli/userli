import { Controller } from "@hotwired/stimulus";

/**
 * Invitation settings controller for the domain edit form.
 *
 * Manages the interaction between the "Enable Invitations" toggle,
 * the "Automatic Invitations" toggle, and the invitation limit input.
 *
 * Behaviour:
 *   - When the invitations toggle is OFF, the auto-invitations toggle
 *     is disabled (greyed out) and the limit input is hidden.
 *   - The auto-invitations toggle reflects whether the limit is > 0.
 *   - When the auto-invitations toggle is switched ON, the limit input
 *     is revealed (pre-filled with the previous value, or a default).
 *   - When the auto-invitations toggle is switched OFF, the limit input
 *     is hidden and its value is set to 0.
 *
 * Targets:
 *   - invitationsToggle: The "Enable Invitations" checkbox (Symfony form field).
 *   - autoToggle:        The standalone "Automatic Invitations" checkbox.
 *   - autoSection:       The wrapper around the auto-toggle (greyed out when disabled).
 *   - limitInput:        The invitation limit number input (Symfony form field).
 *   - limitSection:      The wrapper around the limit input (shown/hidden).
 *
 * Values:
 *   - defaultLimit: Fallback limit when auto-invitations are toggled ON
 *                   but no previous value exists (default: 3).
 */
export default class extends Controller<HTMLElement> {
  declare invitationsToggleTarget: HTMLInputElement;
  declare autoToggleTarget: HTMLInputElement;
  declare autoSectionTarget: HTMLElement;
  declare limitInputTarget: HTMLInputElement;
  declare limitSectionTarget: HTMLElement;
  declare defaultLimitValue: number;

  static targets = [
    "invitationsToggle",
    "autoToggle",
    "autoSection",
    "limitInput",
    "limitSection",
  ];

  static values = {
    defaultLimit: { type: Number, default: 3 },
  };

  /**
   * The last non-zero limit value, so we can restore it when re-enabling.
   */
  private _previousLimit: number = 0;

  connect(): void {
    const currentLimit = parseInt(this.limitInputTarget.value, 10) || 0;
    this._previousLimit = currentLimit > 0 ? currentLimit : this.defaultLimitValue;

    // Set the auto-toggle initial state based on the current limit value.
    this.autoToggleTarget.checked = currentLimit > 0;

    this._sync();
  }

  /**
   * Called when the "Enable Invitations" toggle changes.
   */
  invitationsChanged(): void {
    if (!this.invitationsToggleTarget.checked) {
      // Turning invitations OFF implies auto-invitations OFF too.
      // Remember the current limit so it can be restored.
      const current = parseInt(this.limitInputTarget.value, 10) || 0;
      if (current > 0) {
        this._previousLimit = current;
      }
      this.autoToggleTarget.checked = false;
      this.limitInputTarget.value = "0";
    }

    this._sync();
  }

  /**
   * Called when the "Automatic Invitations" toggle changes.
   */
  autoChanged(): void {
    if (this.autoToggleTarget.checked) {
      // Turning ON: restore the previous limit (or default).
      this.limitInputTarget.value = String(this._previousLimit);
    } else {
      // Turning OFF: remember current value, set to 0.
      const current = parseInt(this.limitInputTarget.value, 10) || 0;
      if (current > 0) {
        this._previousLimit = current;
      }
      this.limitInputTarget.value = "0";
    }

    this._sync();
  }

  /**
   * Called when the limit input value changes directly.
   */
  limitChanged(): void {
    const value = parseInt(this.limitInputTarget.value, 10) || 0;
    if (value > 0) {
      this._previousLimit = value;
    }
  }

  /**
   * Synchronise the visual state of all elements.
   */
  private _sync(): void {
    const invitationsEnabled = this.invitationsToggleTarget.checked;
    const autoEnabled = this.autoToggleTarget.checked;

    // Auto-toggle: disabled & greyed out when invitations are off.
    this.autoToggleTarget.disabled = !invitationsEnabled;
    if (invitationsEnabled) {
      this.autoSectionTarget.classList.remove("opacity-50", "pointer-events-none");
    } else {
      this.autoSectionTarget.classList.add("opacity-50", "pointer-events-none");
    }

    // Limit input: visible only when both toggles are on.
    const showLimit = invitationsEnabled && autoEnabled;
    if (showLimit) {
      this.limitSectionTarget.classList.remove("hidden");
      this.limitInputTarget.min = "1";
    } else {
      this.limitSectionTarget.classList.add("hidden");
      this.limitInputTarget.removeAttribute("min");
    }
  }
}

import { Controller } from "@hotwired/stimulus";

/**
 * Flash notification controller.
 *
 * Handles dismiss-on-click and optional auto-dismiss with a slide-out
 * animation. Each flash message gets its own controller instance.
 *
 * Usage:
 *   <div data-controller="flash-notification"
 *        data-flash-notification-auto-dismiss-value="5000">
 *     <button data-action="flash-notification#dismiss">X</button>
 *   </div>
 *
 * Values:
 *   autoDismiss (Number): Delay in ms before auto-dismissing. 0 = disabled.
 */
export default class extends Controller {
  static values = {
    autoDismiss: { type: Number, default: 0 },
  };

  connect() {
    this._timer = null;

    if (this.autoDismissValue > 0) {
      this._timer = setTimeout(() => this.dismiss(), this.autoDismissValue);
    }
  }

  disconnect() {
    if (this._timer) {
      clearTimeout(this._timer);
      this._timer = null;
    }
  }

  dismiss() {
    if (this._timer) {
      clearTimeout(this._timer);
      this._timer = null;
    }

    this.element.style.opacity = "0";
    this.element.style.transform = "translateX(100%)";

    setTimeout(() => this.element.remove(), 300);
  }
}

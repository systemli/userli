import { Controller } from "@hotwired/stimulus";

/**
 * Reusable dropdown controller for toggling menus.
 *
 * Supports two modes configured via the `mode` value:
 *   - "animated" (default): Uses opacity/scale CSS transitions.
 *   - "simple": Toggles the `hidden` class on the menu target.
 *
 * Targets:
 *   - button:  The toggle trigger element. Gets aria-expanded updated.
 *   - menu:    The dropdown/panel that is shown/hidden.
 *   - arrow:   (optional) A chevron icon that rotates 180° when open.
 *   - iconOpen:  (optional, simple mode) Icon shown when menu is closed.
 *   - iconClose: (optional, simple mode) Icon shown when menu is open.
 *
 * Usage (animated):
 *   <div data-controller="dropdown">
 *     <button data-dropdown-target="button" data-action="dropdown#toggle">...</button>
 *     <div data-dropdown-target="menu" class="opacity-0 scale-95 pointer-events-none ...">
 *       ...
 *     </div>
 *   </div>
 *
 * Usage (simple):
 *   <div data-controller="dropdown" data-dropdown-mode-value="simple">
 *     <button data-dropdown-target="button" data-action="dropdown#toggle">...</button>
 *     <div data-dropdown-target="menu" class="hidden">...</div>
 *     <svg data-dropdown-target="iconOpen">...</svg>
 *     <svg data-dropdown-target="iconClose" class="hidden">...</svg>
 *   </div>
 */
export default class extends Controller {
  declare modeValue: string;
  declare hasMenuTarget: boolean;
  declare menuTarget: HTMLElement;
  declare hasButtonTarget: boolean;
  declare buttonTarget: HTMLElement;
  declare hasArrowTarget: boolean;
  declare arrowTarget: HTMLElement;
  declare hasIconOpenTarget: boolean;
  declare iconOpenTarget: HTMLElement;
  declare hasIconCloseTarget: boolean;
  declare iconCloseTarget: HTMLElement;

  static targets = ["button", "menu", "arrow", "iconOpen", "iconClose"];
  static values = { mode: { type: String, default: "animated" } };

  private isOpen: boolean = false;

  connect(): void {
    this._onOutsideClick = this._onOutsideClick.bind(this);
    this._onKeydown = this._onKeydown.bind(this);

    document.addEventListener("click", this._onOutsideClick);
    document.addEventListener("keydown", this._onKeydown);
  }

  disconnect(): void {
    document.removeEventListener("click", this._onOutsideClick);
    document.removeEventListener("keydown", this._onKeydown);
  }

  toggle(event: Event): void {
    event.preventDefault();
    event.stopPropagation();

    if (this.isOpen) {
      this.close();
    } else {
      this.open();
    }
  }

  open(): void {
    if (!this.hasMenuTarget) return;

    this.isOpen = true;

    if (this.modeValue === "simple") {
      this.menuTarget.classList.remove("hidden");
      if (this.hasIconOpenTarget) this.iconOpenTarget.classList.add("hidden");
      if (this.hasIconCloseTarget)
        this.iconCloseTarget.classList.remove("hidden");
    } else {
      this.menuTarget.classList.remove(
        "opacity-0",
        "scale-95",
        "pointer-events-none",
      );
      this.menuTarget.classList.add("opacity-100", "scale-100");
      if (this.hasArrowTarget) this.arrowTarget.classList.add("rotate-180");
    }

    if (this.hasButtonTarget) {
      this.buttonTarget.setAttribute("aria-expanded", "true");
    }
  }

  close(): void {
    if (!this.hasMenuTarget) return;

    this.isOpen = false;

    if (this.modeValue === "simple") {
      this.menuTarget.classList.add("hidden");
      if (this.hasIconOpenTarget)
        this.iconOpenTarget.classList.remove("hidden");
      if (this.hasIconCloseTarget) this.iconCloseTarget.classList.add("hidden");
    } else {
      this.menuTarget.classList.remove("opacity-100", "scale-100");
      this.menuTarget.classList.add(
        "opacity-0",
        "scale-95",
        "pointer-events-none",
      );
      if (this.hasArrowTarget) this.arrowTarget.classList.remove("rotate-180");
    }

    if (this.hasButtonTarget) {
      this.buttonTarget.setAttribute("aria-expanded", "false");
    }
  }

  private _onOutsideClick(event: Event): void {
    if (this.isOpen && !this.element.contains(event.target as Node)) {
      this.close();
    }
  }

  private _onKeydown(event: KeyboardEvent): void {
    if (event.key === "Escape" && this.isOpen) {
      this.close();
      if (this.hasButtonTarget) this.buttonTarget.focus();
    }
  }
}

import { Controller } from "@hotwired/stimulus";

/**
 * Reusable accessible modal dialog controller.
 *
 * Targets:
 *   - overlay:    The backdrop overlay element (covers the whole screen).
 *   - dialog:     The dialog panel element (the centered card).
 *   - emailField: (optional) A hidden input whose value is set when opening.
 *   - title:      (optional) An element whose text content is updated on open.
 *   - autofocus:  (optional) The element to focus when the modal opens.
 *                 Falls back to the first focusable element in the dialog.
 *
 * Actions:
 *   - open:           Opens the modal. Accepts an `email` param via
 *                     data-modal-email-param to populate the hidden field.
 *   - close:          Closes the modal.
 *   - backdropClose:  Closes only when clicking the backdrop itself.
 *
 * Accessibility:
 *   - Focus is trapped within the dialog while open (Tab/Shift+Tab cycle).
 *   - Focus is restored to the trigger element on close.
 *   - First focusable element (or autofocus target) receives focus on open.
 *   - Escape key closes the modal.
 *   - Body scroll is locked while open.
 *   - Templates should set aria-modal="true", role="dialog", and
 *     aria-labelledby on the overlay element.
 *
 * Usage:
 *   <div data-controller="modal">
 *     <button data-action="modal#open"
 *             data-modal-email-param="user@example.com">
 *       Upload
 *     </button>
 *
 *     <div data-modal-target="overlay"
 *          data-action="click->modal#backdropClose"
 *          class="hidden fixed inset-0 z-50 ..."
 *          aria-modal="true"
 *          role="dialog"
 *          aria-labelledby="modal-title">
 *       <div data-modal-target="dialog" class="...">
 *         <h2 id="modal-title">Dialog Title</h2>
 *         <input data-modal-target="autofocus" type="text">
 *         ...
 *       </div>
 *     </div>
 *   </div>
 */
export default class extends Controller {
  declare hasOverlayTarget: boolean;
  declare overlayTarget: HTMLElement;
  declare hasDialogTarget: boolean;
  declare dialogTarget: HTMLElement;
  declare hasEmailFieldTarget: boolean;
  declare emailFieldTarget: HTMLInputElement;
  declare hasTitleTarget: boolean;
  declare titleTarget: HTMLElement;
  declare hasAutofocusTarget: boolean;
  declare autofocusTarget: HTMLElement;

  static targets = ["overlay", "dialog", "emailField", "title", "autofocus"];

  private isOpen: boolean = false;
  private boundOnKeydown: ((event: KeyboardEvent) => void) | null = null;
  private previouslyFocusedElement: HTMLElement | null = null;

  connect(): void {
    this.boundOnKeydown = this.onKeydown.bind(this);
    document.addEventListener("keydown", this.boundOnKeydown);
  }

  disconnect(): void {
    if (this.boundOnKeydown) {
      document.removeEventListener("keydown", this.boundOnKeydown);
    }
  }

  open(event: Event & { params?: { email?: string } }): void {
    if (!this.hasOverlayTarget) return;

    this.previouslyFocusedElement = document.activeElement as HTMLElement;

    const email = event.params?.email ?? "";

    if (this.hasEmailFieldTarget && email) {
      this.emailFieldTarget.value = email;
    }

    if (this.hasTitleTarget && email) {
      this.titleTarget.textContent = email;
    }

    this.overlayTarget.classList.remove("hidden");
    // Force a reflow so the transition plays after removing hidden.
    void this.overlayTarget.offsetHeight;
    this.overlayTarget.classList.remove("opacity-0");
    this.overlayTarget.classList.add("opacity-100");

    if (this.hasDialogTarget) {
      this.dialogTarget.classList.remove("opacity-0", "scale-95");
      this.dialogTarget.classList.add("opacity-100", "scale-100");
    }

    this.isOpen = true;
    document.body.classList.add("overflow-hidden");

    // Wait for the next frame so focus lands on a visible element
    // (the transition from hidden → visible needs one frame to complete).
    requestAnimationFrame(() => {
      this.focusFirstElement();
    });
  }

  close(): void {
    if (!this.hasOverlayTarget) return;

    this.overlayTarget.classList.remove("opacity-100");
    this.overlayTarget.classList.add("opacity-0");

    if (this.hasDialogTarget) {
      this.dialogTarget.classList.remove("opacity-100", "scale-100");
      this.dialogTarget.classList.add("opacity-0", "scale-95");
    }

    // Wait for the transition to finish before hiding.
    setTimeout(() => {
      this.overlayTarget.classList.add("hidden");
    }, 200);

    this.isOpen = false;
    document.body.classList.remove("overflow-hidden");

    // Restore focus immediately so screen readers don't lose their position
    // during the 200 ms closing transition.
    if (this.previouslyFocusedElement) {
      this.previouslyFocusedElement.focus();
      this.previouslyFocusedElement = null;
    }
  }

  backdropClose(event: Event): void {
    // Only close when clicking the overlay itself, not the dialog content.
    if (event.target === this.overlayTarget) {
      this.close();
    }
  }

  private onKeydown(event: KeyboardEvent): void {
    if (!this.isOpen) return;

    if (event.key === "Escape") {
      this.close();
      return;
    }

    if (event.key === "Tab") {
      this.trapFocus(event);
    }
  }

  /**
   * Traps Tab/Shift+Tab within the dialog panel.
   *
   * When the user presses Tab on the last focusable element, focus wraps
   * to the first. When pressing Shift+Tab on the first, it wraps to the
   * last. This keeps keyboard users inside the modal while it's open.
   */
  private trapFocus(event: KeyboardEvent): void {
    const target = this.hasDialogTarget
      ? this.dialogTarget
      : this.overlayTarget;
    const focusableElements = this.getFocusableElements(target);

    if (focusableElements.length === 0) return;

    const firstElement = focusableElements[0];
    const lastElement = focusableElements[focusableElements.length - 1];

    if (event.shiftKey) {
      // Shift+Tab: if on the first element, wrap to the last.
      if (document.activeElement === firstElement) {
        event.preventDefault();
        lastElement.focus();
      }
    } else {
      // Tab: if on the last element, wrap to the first.
      if (document.activeElement === lastElement) {
        event.preventDefault();
        firstElement.focus();
      }
    }
  }

  /**
   * Returns all focusable elements within the given container,
   * excluding disabled and hidden elements.
   */
  private getFocusableElements(container: HTMLElement): HTMLElement[] {
    const selector = [
      "a[href]",
      "button:not([disabled])",
      'input:not([disabled]):not([type="hidden"])',
      "select:not([disabled])",
      "textarea:not([disabled])",
      '[tabindex]:not([tabindex="-1"])',
    ].join(", ");

    return Array.from(
      container.querySelectorAll<HTMLElement>(selector),
    ).filter((el) => !el.closest("[hidden]") && el.offsetParent !== null);
  }

  /**
   * Focuses the autofocus target or the first focusable element
   * within the dialog.
   */
  private focusFirstElement(): void {
    // Explicit autofocus target takes priority.
    if (this.hasAutofocusTarget) {
      this.autofocusTarget.focus();
      return;
    }

    // Fallback: first focusable element in the dialog.
    const target = this.hasDialogTarget
      ? this.dialogTarget
      : this.overlayTarget;
    const focusableElements = this.getFocusableElements(target);

    if (focusableElements.length > 0) {
      focusableElements[0].focus();
    }
  }
}

import { Controller } from "@hotwired/stimulus";

/**
 * Reusable modal dialog controller.
 *
 * Targets:
 *   - overlay:    The backdrop overlay element (covers the whole screen).
 *   - dialog:     The dialog panel element (the centered card).
 *   - emailField: (optional) A hidden input whose value is set when opening.
 *   - title:      (optional) An element whose text content is updated on open.
 *
 * Actions:
 *   - open:           Opens the modal.  Accepts an `email` param via
 *                     data-modal-email-param to populate the hidden field.
 *   - close:          Closes the modal.
 *   - backdropClose:  Closes only when clicking the backdrop itself.
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
 *          class="hidden fixed inset-0 z-50 ...">
 *       <div data-modal-target="dialog" class="...">
 *         <input type="hidden" data-modal-target="emailField" name="email">
 *         <span data-modal-target="title"></span>
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

  static targets = ["overlay", "dialog", "emailField", "title"];

  private isOpen: boolean = false;
  private boundOnKeydown: ((event: KeyboardEvent) => void) | null = null;

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
  }

  backdropClose(event: Event): void {
    // Only close when clicking the overlay itself, not the dialog content.
    if (event.target === this.overlayTarget) {
      this.close();
    }
  }

  private onKeydown(event: KeyboardEvent): void {
    if (event.key === "Escape" && this.isOpen) {
      this.close();
    }
  }
}

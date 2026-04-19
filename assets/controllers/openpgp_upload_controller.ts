import { Controller } from "@hotwired/stimulus";

/**
 * Two-step modal controller for OpenPGP key upload.
 *
 * Keeps the keyfile step and the account-password confirmation step in
 * visually distinct overlays so the account password can never be mistaken
 * for the OpenPGP key's passphrase. Both overlays share a single form.
 *
 * Targets:
 *   - uploadOverlay / uploadDialog:      Step 1 overlay + centered dialog
 *   - passwordOverlay / passwordDialog:  Step 2 overlay + centered dialog
 *   - emailField:                        Hidden email input, populated on open
 *   - title:                             Email shown in the step-1 header
 *   - passwordEmail:                     Email shown in the step-2 description
 *   - passwordField:                     The account-password input (focused on advance)
 *   - form:                              The submittable form element
 *
 * Actions:
 *   - open:                 Opens step 1. Expects `data-*-email-param` and
 *                           `data-*-replace-param` (truthy when replacing an
 *                           existing key).
 *   - advance:              Submits the form directly when not replacing, or
 *                           transitions to step 2 when replacing.
 *   - back:                 Returns to step 1 from step 2.
 *   - close:                Closes both overlays.
 *   - backdropCloseUpload:  Closes when the step-1 backdrop is clicked.
 *   - backdropClosePassword: Closes when the step-2 backdrop is clicked.
 */
export default class OpenpgpUploadController extends Controller {
  static readonly targets = [
    "uploadOverlay",
    "uploadDialog",
    "passwordOverlay",
    "passwordDialog",
    "emailField",
    "title",
    "passwordEmail",
    "passwordField",
    "form",
  ];

  declare hasUploadOverlayTarget: boolean;
  declare uploadOverlayTarget: HTMLElement;
  declare hasUploadDialogTarget: boolean;
  declare uploadDialogTarget: HTMLElement;
  declare hasPasswordOverlayTarget: boolean;
  declare passwordOverlayTarget: HTMLElement;
  declare hasPasswordDialogTarget: boolean;
  declare passwordDialogTarget: HTMLElement;
  declare hasEmailFieldTarget: boolean;
  declare emailFieldTarget: HTMLInputElement;
  declare hasTitleTarget: boolean;
  declare titleTarget: HTMLElement;
  declare hasPasswordEmailTarget: boolean;
  declare passwordEmailTarget: HTMLElement;
  declare hasPasswordFieldTarget: boolean;
  declare passwordFieldTarget: HTMLInputElement;
  declare hasFormTarget: boolean;
  declare formTarget: HTMLFormElement;

  private needsPassword = false;
  private previouslyFocusedElement: HTMLElement | null = null;
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

  open(
    event: Event & {
      params?: { email?: string; replace?: boolean };
    },
  ): void {
    if (!this.hasUploadOverlayTarget) return;

    this.previouslyFocusedElement = document.activeElement as HTMLElement;

    const email = event.params?.email ?? "";
    this.needsPassword = Boolean(event.params?.replace);

    if (this.hasEmailFieldTarget && email) {
      this.emailFieldTarget.value = email;
    }
    if (this.hasTitleTarget) {
      this.titleTarget.textContent = email;
    }
    if (this.hasPasswordEmailTarget) {
      this.passwordEmailTarget.textContent = email;
    }
    if (this.hasPasswordFieldTarget) {
      this.passwordFieldTarget.value = "";
    }

    this.showOverlay(this.uploadOverlayTarget, this.uploadDialogTarget);
    document.body.classList.add("overflow-hidden");

    requestAnimationFrame(() => {
      this.focusFirstIn(this.uploadDialogTarget);
    });
  }

  advance(): void {
    if (!this.needsPassword) {
      if (this.hasFormTarget) this.formTarget.requestSubmit();
      return;
    }

    this.hideOverlay(this.uploadOverlayTarget, this.uploadDialogTarget);
    this.showOverlay(this.passwordOverlayTarget, this.passwordDialogTarget);

    requestAnimationFrame(() => {
      if (this.hasPasswordFieldTarget) {
        this.passwordFieldTarget.focus();
      } else {
        this.focusFirstIn(this.passwordDialogTarget);
      }
    });
  }

  back(): void {
    this.hideOverlay(this.passwordOverlayTarget, this.passwordDialogTarget);
    this.showOverlay(this.uploadOverlayTarget, this.uploadDialogTarget);

    requestAnimationFrame(() => {
      this.focusFirstIn(this.uploadDialogTarget);
    });
  }

  close(): void {
    this.hideOverlay(this.uploadOverlayTarget, this.uploadDialogTarget);
    this.hideOverlay(this.passwordOverlayTarget, this.passwordDialogTarget);
    if (this.hasPasswordFieldTarget) {
      this.passwordFieldTarget.value = "";
    }
    document.body.classList.remove("overflow-hidden");

    if (this.previouslyFocusedElement) {
      this.previouslyFocusedElement.focus();
      this.previouslyFocusedElement = null;
    }
  }

  backdropCloseUpload(event: Event): void {
    if (event.target === this.uploadOverlayTarget) this.close();
  }

  backdropClosePassword(event: Event): void {
    if (event.target === this.passwordOverlayTarget) this.close();
  }

  private showOverlay(overlay: HTMLElement, dialog: HTMLElement): void {
    overlay.classList.remove("hidden");
    // Force reflow so the opacity/scale transition plays after unhiding.
    overlay.getBoundingClientRect();
    overlay.classList.remove("opacity-0");
    overlay.classList.add("opacity-100");
    dialog.classList.remove("opacity-0", "scale-95");
    dialog.classList.add("opacity-100", "scale-100");
  }

  private hideOverlay(overlay: HTMLElement, dialog: HTMLElement): void {
    overlay.classList.remove("opacity-100");
    overlay.classList.add("opacity-0");
    dialog.classList.remove("opacity-100", "scale-100");
    dialog.classList.add("opacity-0", "scale-95");

    setTimeout(() => overlay.classList.add("hidden"), 200);
  }

  private onKeydown(event: KeyboardEvent): void {
    if (event.key !== "Escape") return;
    if (this.isVisible()) this.close();
  }

  private isVisible(): boolean {
    const uploadOpen =
      this.hasUploadOverlayTarget &&
      !this.uploadOverlayTarget.classList.contains("hidden");
    const passwordOpen =
      this.hasPasswordOverlayTarget &&
      !this.passwordOverlayTarget.classList.contains("hidden");
    return uploadOpen || passwordOpen;
  }

  private focusFirstIn(container: HTMLElement): void {
    const selector = [
      "input:not([disabled]):not([type='hidden'])",
      "textarea:not([disabled])",
      "button:not([disabled])",
      "a[href]",
    ].join(", ");
    const el = container.querySelector<HTMLElement>(selector);
    if (el) el.focus();
  }
}

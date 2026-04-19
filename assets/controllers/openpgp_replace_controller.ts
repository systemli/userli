import ModalController from "./modal_controller";

/**
 * Password verification modal for replacing an OpenPGP key.
 *
 * Extends the generic ModalController and adds two extra behaviors needed for
 * the two-step replace flow on the OpenPGP account page:
 *
 *   1. An `arm` action that is fired (alongside `modal#open`) by the "Replace"
 *      button on identity cards with an existing key. It flags that the next
 *      submit of the upload form should be intercepted so the user can confirm
 *      their account password first.
 *
 *   2. A submit listener on the upload form (`#openpgp-upload-form`). When the
 *      controller is armed, the first submit is prevented and this password
 *      modal is opened. The password input inside this modal is bound to the
 *      upload form via HTML5 `form="…"` so once the user confirms, the next
 *      submit POSTs the upload payload together with the password.
 *
 * Arming is cleared when the user closes the modal or advances past it, so a
 * later click on "Upload" (for an identity without a key) won't trigger
 * password verification.
 */
export default class extends ModalController {
  private armed = false;
  private armedEmail = "";
  private uploadForm: HTMLFormElement | null = null;
  private boundOnUploadSubmit: ((event: Event) => void) | null = null;

  connect(): void {
    super.connect();

    const form = document.getElementById("openpgp-upload-form");
    if (form instanceof HTMLFormElement) {
      this.uploadForm = form;
      this.boundOnUploadSubmit = this.onUploadSubmit.bind(this);
      form.addEventListener("submit", this.boundOnUploadSubmit);
    }
  }

  disconnect(): void {
    super.disconnect();

    if (this.uploadForm && this.boundOnUploadSubmit) {
      this.uploadForm.removeEventListener("submit", this.boundOnUploadSubmit);
    }
  }

  arm(event: Event & { params?: { email?: string } }): void {
    this.armed = true;
    this.armedEmail = event.params?.email ?? "";
  }

  disarm(): void {
    this.armed = false;
    this.armedEmail = "";
  }

  close(): void {
    super.close();
    this.disarm();
  }

  private onUploadSubmit(event: Event): void {
    if (!this.armed) return;
    // If this modal is already visible, the submit is coming from step 2 —
    // let it through so the form can actually POST.
    if (this.isVisible()) return;

    event.preventDefault();
    this.openProgrammatically();
  }

  private isVisible(): boolean {
    return (
      this.hasOverlayTarget && !this.overlayTarget.classList.contains("hidden")
    );
  }

  private openProgrammatically(): void {
    // ModalController.open expects a DOM Event; synthesize one. We only need
    // to satisfy the parts the parent actually reads: `type` (to skip its
    // submit-intercept branch) and `params.email`.
    const syntheticEvent = {
      type: "programmatic",
      params: { email: this.armedEmail },
    } as unknown as Event & { params: { email?: string } };

    super.open(syntheticEvent);
  }
}

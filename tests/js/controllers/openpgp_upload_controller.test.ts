import { Application } from "@hotwired/stimulus";
import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import OpenpgpUploadController from "../../../assets/controllers/openpgp_upload_controller";
import { startController } from "../helpers/stimulus";

const HTML = `
  <div data-controller="openpgp-upload">
    <button id="trigger-new"
            data-action="openpgp-upload#open"
            data-openpgp-upload-email-param="alice@example.org">
      Upload
    </button>
    <button id="trigger-replace"
            data-action="openpgp-upload#open"
            data-openpgp-upload-email-param="alice@example.org"
            data-openpgp-upload-replace-param="true">
      Replace
    </button>

    <form method="post" data-openpgp-upload-target="form">
      <input data-openpgp-upload-target="emailField" type="hidden" name="email">

      <div data-openpgp-upload-target="uploadOverlay" class="hidden opacity-0">
        <div data-openpgp-upload-target="uploadDialog" class="opacity-0 scale-95">
          <span data-openpgp-upload-target="title"></span>
          <textarea name="keyText"></textarea>
          <button id="advance" type="button" data-action="openpgp-upload#advance">Continue</button>
          <button id="close-upload" type="button" data-action="openpgp-upload#close">Cancel</button>
        </div>
      </div>

      <div data-openpgp-upload-target="passwordOverlay" class="hidden opacity-0">
        <div data-openpgp-upload-target="passwordDialog" class="opacity-0 scale-95">
          <span data-openpgp-upload-target="passwordEmail"></span>
          <input data-openpgp-upload-target="passwordField" type="password" name="password">
          <button id="back" type="button" data-action="openpgp-upload#back">Back</button>
          <button id="submit" type="submit">Publish</button>
        </div>
      </div>
    </form>
  </div>`;

describe("OpenpgpUploadController", () => {
  let app: Application | null = null;

  async function start(html: string) {
    const result = await startController(OpenpgpUploadController, html, "openpgp-upload");
    app = result.application;
    return result;
  }

  beforeEach(() => {
    vi.useFakeTimers();
  });

  afterEach(() => {
    app?.stop();
    app = null;
    vi.useRealTimers();
  });

  it("opens the upload overlay and populates the email", async () => {
    const { element } = await start(HTML);
    const trigger = element.querySelector<HTMLButtonElement>("#trigger-new")!;
    const uploadOverlay = element.querySelector<HTMLElement>(
      "[data-openpgp-upload-target='uploadOverlay']",
    )!;
    const title = element.querySelector<HTMLElement>(
      "[data-openpgp-upload-target='title']",
    )!;
    const emailField = element.querySelector<HTMLInputElement>(
      "[data-openpgp-upload-target='emailField']",
    )!;

    trigger.click();

    expect(uploadOverlay.classList.contains("hidden")).toBe(false);
    expect(title.textContent).toBe("alice@example.org");
    expect(emailField.value).toBe("alice@example.org");
  });

  it("submits the form directly when replace is not requested", async () => {
    const { element } = await start(HTML);
    const trigger = element.querySelector<HTMLButtonElement>("#trigger-new")!;
    const advance = element.querySelector<HTMLButtonElement>("#advance")!;
    const form = element.querySelector<HTMLFormElement>(
      "[data-openpgp-upload-target='form']",
    )!;
    const requestSubmit = vi
      .spyOn(form, "requestSubmit")
      .mockImplementation(() => {});

    trigger.click();
    advance.click();

    expect(requestSubmit).toHaveBeenCalledOnce();
  });

  it("transitions to the password overlay when replacing", async () => {
    const { element } = await start(HTML);
    const trigger = element.querySelector<HTMLButtonElement>("#trigger-replace")!;
    const advance = element.querySelector<HTMLButtonElement>("#advance")!;
    const uploadOverlay = element.querySelector<HTMLElement>(
      "[data-openpgp-upload-target='uploadOverlay']",
    )!;
    const passwordOverlay = element.querySelector<HTMLElement>(
      "[data-openpgp-upload-target='passwordOverlay']",
    )!;
    const form = element.querySelector<HTMLFormElement>(
      "[data-openpgp-upload-target='form']",
    )!;
    const requestSubmit = vi
      .spyOn(form, "requestSubmit")
      .mockImplementation(() => {});

    trigger.click();
    advance.click();
    // Let the fade-out timer elapse so the overlay receives `hidden`.
    vi.runAllTimers();

    expect(uploadOverlay.classList.contains("hidden")).toBe(true);
    expect(passwordOverlay.classList.contains("hidden")).toBe(false);
    expect(requestSubmit).not.toHaveBeenCalled();
  });

  it("back returns from the password overlay to the upload overlay", async () => {
    const { element } = await start(HTML);
    const trigger = element.querySelector<HTMLButtonElement>("#trigger-replace")!;
    const advance = element.querySelector<HTMLButtonElement>("#advance")!;
    const back = element.querySelector<HTMLButtonElement>("#back")!;
    const uploadOverlay = element.querySelector<HTMLElement>(
      "[data-openpgp-upload-target='uploadOverlay']",
    )!;
    const passwordOverlay = element.querySelector<HTMLElement>(
      "[data-openpgp-upload-target='passwordOverlay']",
    )!;

    trigger.click();
    advance.click();
    vi.runAllTimers();
    back.click();
    vi.runAllTimers();

    expect(uploadOverlay.classList.contains("hidden")).toBe(false);
    expect(passwordOverlay.classList.contains("hidden")).toBe(true);
  });

  it("close hides both overlays and clears the password", async () => {
    const { element } = await start(HTML);
    const trigger = element.querySelector<HTMLButtonElement>("#trigger-replace")!;
    const advance = element.querySelector<HTMLButtonElement>("#advance")!;
    const closeBtn = element.querySelector<HTMLButtonElement>("#close-upload")!;
    const uploadOverlay = element.querySelector<HTMLElement>(
      "[data-openpgp-upload-target='uploadOverlay']",
    )!;
    const passwordOverlay = element.querySelector<HTMLElement>(
      "[data-openpgp-upload-target='passwordOverlay']",
    )!;
    const passwordField = element.querySelector<HTMLInputElement>(
      "[data-openpgp-upload-target='passwordField']",
    )!;

    trigger.click();
    advance.click();
    vi.runAllTimers();
    passwordField.value = "sekret";
    closeBtn.click();
    vi.runAllTimers();

    expect(uploadOverlay.classList.contains("hidden")).toBe(true);
    expect(passwordOverlay.classList.contains("hidden")).toBe(true);
    expect(passwordField.value).toBe("");
  });

  it("closes on Escape key press", async () => {
    const { element } = await start(HTML);
    const trigger = element.querySelector<HTMLButtonElement>("#trigger-new")!;
    const uploadOverlay = element.querySelector<HTMLElement>(
      "[data-openpgp-upload-target='uploadOverlay']",
    )!;

    trigger.click();
    expect(uploadOverlay.classList.contains("hidden")).toBe(false);

    document.dispatchEvent(new KeyboardEvent("keydown", { key: "Escape" }));
    vi.runAllTimers();

    expect(uploadOverlay.classList.contains("hidden")).toBe(true);
  });
});
